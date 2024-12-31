<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class Snapshot
{
    public FilesystemAdapter $disk;

    public string $fileName;

    public string $name;

    public ?string $compressionExtension = null;

    private bool $useStream = false;

    public const STREAM_BUFFER_SIZE = 16384;

    protected Factory $filesystemFactory;

    public function __construct(FilesystemAdapter $disk, string $fileName)
    {
        $this->disk = $disk;
        $this->fileName = $fileName;

        $pathinfo = pathinfo($fileName);

        if ($pathinfo['extension'] === 'gz') {
            $this->compressionExtension = $pathinfo['extension'];
            $fileName = $pathinfo['filename'];
        }

        $this->name = pathinfo($fileName, PATHINFO_FILENAME);
        $this->filesystemFactory = app(Factory::class);
    }

    public function useStream(): self
    {
        $this->useStream = true;
        return $this;
    }

    public function load(?string $connectionName = null, bool $dropTables = true): void
    {
        event(new LoadingSnapshot($this));

        if ($connectionName !== null) {
            DB::setDefaultConnection($connectionName);
        }

        if ($dropTables) {
            $this->dropAllCurrentTables();
        }

        $this->useStream ? $this->loadStream($connectionName) : $this->loadAsync($connectionName);

        event(new LoadedSnapshot($this));
    }

    protected function loadAsync(?string $connectionName = null): void
    {
        $dbDumpContents = $this->disk->get($this->fileName);

        if ($this->compressionExtension === 'gz') {
            $dbDumpContents = gzdecode($dbDumpContents);
        }

        if (empty(trim($dbDumpContents))) {
            return;
        }

        DB::connection($connectionName)->unprepared($dbDumpContents);
    }

    protected function isASqlComment(string $line): bool
    {
        return str_starts_with($line, '--');
    }

    protected function shouldIgnoreLine(string $line): bool
    {
        $line = trim($line);
        return empty($line) || $this->isASqlComment($line);
    }

    protected function loadStream(?string $connectionName = null): void
    {
        $temporaryDirectory = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))->create();

        $this->configureFilesystemDisk($temporaryDirectory->path());

        $localDisk = $this->filesystemFactory->disk(self::class);

        try {
            $this->processStream($localDisk, $connectionName);
        } finally {
            $temporaryDirectory->delete();
        }
    }

    private function configureFilesystemDisk(string $path): void
    {
        config([
            'filesystems.disks.' . self::class => [
                'driver' => 'local',
                'root' => $path,
                'throw' => false,
            ],
        ]);
    }

    private function processStream(FilesystemAdapter $localDisk, ?string $connectionName): void
    {
        $this->copyStreamToLocalDisk($localDisk);

        $stream = $this->openStream($localDisk);

        try {
            $this->processStatements($stream, $connectionName);
        } finally {
            $this->closeStream($stream);
        }
    }

    private function copyStreamToLocalDisk(FilesystemAdapter $localDisk): void
    {
        $localDisk->writeStream($this->fileName, $this->disk->readStream($this->fileName));
    }

    private function openStream(FilesystemAdapter $localDisk): mixed
    {
        $stream = $this->compressionExtension === 'gz'
            ? gzopen($localDisk->path($this->fileName), 'r')
            : $localDisk->readStream($this->fileName);

        if (!is_resource($stream)) {
            throw new \RuntimeException("Failed to open stream for file: {$this->fileName}");
        }

        return $stream;
    }

    private function closeStream(mixed $stream): void
    {
        if (!is_resource($stream)) {
            throw new \RuntimeException("Invalid stream provided for closing.");
        }

        $this->compressionExtension === 'gz' ? gzclose($stream) : fclose($stream);
    }

    private function processStatements(mixed $stream, ?string $connectionName): void
    {
        $statement = '';
        while (!feof($stream)) {
            $chunk = $this->readChunk($stream);
            $lines = explode("\n", $chunk);

            foreach ($lines as $idx => $line) {
                if ($this->shouldIgnoreLine($line)) {
                    continue;
                }

                $statement .= $line;

                if ($this->isLastLineOfChunk($lines, $idx)) {
                    break;
                }

                if ($this->isCompleteStatement($statement)) {
                    DB::connection($connectionName)->unprepared($statement);
                    $statement = '';
                }
            }
        }

        if ($this->isCompleteStatement($statement)) {
            DB::connection($connectionName)->unprepared($statement);
        }
    }

    private function readChunk(mixed $stream): string
    {
        return $this->compressionExtension === 'gz'
            ? gzread($stream, self::STREAM_BUFFER_SIZE)
            : fread($stream, self::STREAM_BUFFER_SIZE);
    }

    private function isLastLineOfChunk(array $lines, int $idx): bool
    {
        return count($lines) === $idx + 1;
    }

    private function isCompleteStatement(string $statement): bool
    {
        return str_ends_with(trim($statement), ';');
    }

    public function delete(): void
    {
        event(new DeletingSnapshot($this));
        $this->disk->delete($this->fileName);
        event(new DeletedSnapshot($this->fileName, $this->disk));
    }

    public function size(): int
    {
        return $this->disk->size($this->fileName);
    }

    public function createdAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->disk->lastModified($this->fileName));
    }

    protected function dropAllCurrentTables(): void
    {
        DB::connection(DB::getDefaultConnection())
            ->getSchemaBuilder()
            ->dropAllTables();

        DB::reconnect();
    }
}
