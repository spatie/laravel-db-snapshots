<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class Snapshot
{
    public Disk $disk;

    public string $fileName;

    public string $name;

    public ?string $compressionExtension = null;

    private bool $useStream = false;

    public const STREAM_BUFFER_SIZE = 16384;

    protected Factory $filesystemFactory;

    public function __construct(Disk $disk, string $fileName)
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
        $directory = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))->create();

        config([
            'filesystems.disks.' . self::class => [
                'driver' => 'local',
                'root' => $directory->path(),
                'throw' => false,
            ]
        ]);

        $localDisk = $this->filesystemFactory->disk(self::class);

        try {
            LazyCollection::make(function () use ($localDisk) {
                $localDisk->writeStream($this->fileName, $this->disk->readStream($this->fileName));

                $stream = $this->compressionExtension === 'gz'
                    ? gzopen($localDisk->path($this->fileName), 'r')
                    : $localDisk->readStream($this->fileName);

                $statement = '';
                while (!feof($stream)) {
                    $chunk = $this->compressionExtension === 'gz'
                        ? gzread($stream, self::STREAM_BUFFER_SIZE)
                        : fread($stream, self::STREAM_BUFFER_SIZE);

                    $lines = explode("\n", $chunk);
                    foreach ($lines as $idx => $line) {
                        if ($this->shouldIgnoreLine($line)) {
                            continue;
                        }

                        $statement .= $line;

                        // Carry-over the last line to the next chunk since it
                        // is possible that this chunk finished mid-line right on
                        // a semi-colon.
                        if (count($lines) == $idx + 1) {
                            break;
                        }

                        if (str_ends_with(trim($statement), ';')) {
                            yield $statement;
                            $statement = '';
                        }
                    }
                }

                if (str_ends_with(trim($statement), ';')) {
                    yield $statement;
                }

                if ($this->compressionExtension === 'gz') {
                    gzclose($stream);
                } else {
                    fclose($stream);
                }
            })->each(function (string $statement) use ($connectionName) {
                DB::connection($connectionName)->unprepared($statement);
            });
        } finally {
            $directory->delete();
        }
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
