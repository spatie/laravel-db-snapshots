<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Storage;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Spatie\DbSnapshots\Exceptions\CannotLoadSnapshot;

class Snapshot
{
    public Disk $disk;

    public string $fileName;

    public string $name;

    public ?string $compressionExtension = null;

    private bool $useStream = false;

    const STREAM_BUFFER_SIZE = 16384;

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
    }

    public function useStream()
    {
        $this->useStream = true;

        return $this;
    }

    public function load(string $connectionName = null, bool $dropTables = true): void
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

    protected function getUncompressedLocalSnapshotPath(): string
    {
        $stream = $this->disk->readStream($this->fileName);

        $uncompressedFilePath = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))
                           ->create()
                           ->path('snapshot.sql');

        $fileDest = fopen($uncompressedFilePath, 'w');

        while (feof($stream) !== true) {
            fwrite($fileDest, gzdecode(gzread($stream, self::STREAM_BUFFER_SIZE)));
        }

        fclose($fileDest);

        $this->disk = Storage::disk('local');
        return $uncompressedFilePath;
    }

    protected function loadAsync(string $connectionName = null)
    {
        $dbDumpContents = $this->disk->get($this->fileName);

        if ($this->compressionExtension === 'gz') {
            $dbDumpContents = gzdecode($dbDumpContents);
        }

        DB::connection($connectionName)->unprepared($dbDumpContents);
    }

    protected function isASqlComment(string $line): bool
    {
        return substr($line, 0, 2) === '--';
    }

    protected function shouldIgnoreLine(string $line): bool
    {
        $line = trim($line);
        return empty($line) || $this->isASqlComment($line);
    }

    protected function loadStream(string $connectionName = null)
    {
        $snapshotFilePath = $this->compressionExtension === 'gz'
                            ? $this->getUncompressedLocalSnapshotPath()
                            : $this->disk->path($this->fileName);

        if (!is_readable($snapshotFilePath)) {
            throw CannotLoadSnapshot::fileNotReadable($snapshotFilePath);
        }

        LazyCollection::make(function() use ($snapshotFilePath, $bar) {
            $handle = fopen($snapshotFilePath, 'r');

            $statement = '';
            while (($line = fgets($handle)) !== false) {
                if ($this->shouldIgnoreLine($line)) {
                    continue;
                }

                $statement .= $line;

                if (substr(trim($statement), -1, 1) === ';') {
                    yield $statement;
                    $statement = '';
                }
            }
        })->each(function (string $statement) use($connectionName) {
            DB::connection($connectionName)->unprepared($statement);
        });
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

    protected function dropAllCurrentTables()
    {
        DB::connection(DB::getDefaultConnection())
            ->getSchemaBuilder()
            ->dropAllTables();

        DB::reconnect();
    }
}
