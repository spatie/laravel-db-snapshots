<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\DbSnapshots\Events\SnapshotStatus;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class Snapshot
{
    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    public $disk;

    /** @var string */
    public $fileName;

    /** @var string */
    public $name;

    /** @var string */
    public $compressionExtension = null;

    /** @var bool */
    private $useStream = false;

    /** @var bool */
    private $showProgress = false;

    /** @var array */
    private $errors = [];

    /** @var int */
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

    public function showProgress()
    {
        $this->showProgress = true;

        return $this;
    }

    public function load(string $connectionName = null)
    {
        event(new LoadingSnapshot($this));

        if ($connectionName !== null) {
            DB::setDefaultConnection($connectionName);
        }

        $this->dropAllCurrentTables();

        $this->useStream ? $this->loadStream($connectionName) : $this->loadAsync($connectionName);

        event(new LoadedSnapshot($this));
    }

    public function getErrors()
    {
        return $this->errors;
    }

    protected function loadAsync(string $connectionName = null)
    {
        $dbDumpContents = $this->disk->get($this->fileName);

        if ($this->compressionExtension === 'gz') {
            event(new SnapshotStatus($this, 'Decompressing snapshot...'));
            $dbDumpContents = gzdecode($dbDumpContents);
        }

        event(new SnapshotStatus($this, 'Importing SQL...'));

        DB::connection($connectionName)->unprepared($dbDumpContents);
    }

    protected function loadStream(string $connectionName = null)
    {
        $dumpFilePath = $this->compressionExtension === 'gz' ?
            $this->downloadExternalSnapshort() :
            $this->disk->path($this->fileName);

        return $this->streamFileIntoDB($dumpFilePath, $connectionName);
    }

    protected function getFileHandler($path): LazyCollection
    {
        return LazyCollection::make(function () use ($path) {
            if ($this->compressionExtension === 'gz') {
                $handle = gzopen($path, 'r');
                while (! gzeof($handle)) {
                    yield gzgets($handle, self::STREAM_BUFFER_SIZE);
                }
            } else {
                $handle = $this->disk->readStream($path);
                while (($line = fgets($handle)) !== false) {
                    yield $line;
                }
            }
        });
    }

    protected function streamFileIntoDB($path, string $connectionName = null)
    {
        if ($connectionName !== null) {
            DB::setDefaultConnection($connectionName);
        }

        $tmpLine = '';
        $counter = $this->showProgress ? 0 : false;

        event(new SnapshotStatus($this, 'Importing SQL...'));

        $this->getFileHandler($path)->each(function ($line) use (&$tmpLine, &$counter, $connectionName) {
            if ($counter !== false && $counter % 500 === 0) {
                echo '.';
            }

            // Skip it if line is a comment
            if (substr($line, 0, 2) === '--' || trim($line) == '') {
                return;
            }

            $tmpLine .= $line;

            // If the line ends with a semicolon, it is the end of the query - run it
            if (substr(trim($line), -1, 1) === ';') {
                try {
                    DB::connection($connectionName)->unprepared($tmpLine);
                } catch (Exception $e) {
                    if ($counter !== false) {
                        echo 'E';
                    }

                    preg_match_all('/INSERT INTO `(.*)`/mU', $e->getMessage(), $matches);

                    if (is_array($matches)) {
                        unset($matches[0]);

                        foreach ($matches as $match) {
                            if (empty($match[0])) {
                                continue;
                            }
                            $tableName = $match[0];
                            if (! isset($this->errors[$tableName])) {
                                $this->errors[$tableName] = 0;
                            }
                            $this->errors[$tableName]++;
                        }
                    }
                }

                $tmpLine = '';
            }
            $counter++;
        });

        if ($counter !== false) {
            echo PHP_EOL;
        }

        if (! empty($this->errors)) {
            return $this->errors;
        }

        return true;
    }

    public function downloadExternalSnapshort()
    {
        $stream = $this->disk->readStream($this->fileName);
        $gzFilePath = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))
                           ->create()
                           ->path('temp-load.tmp').'.gz';
        $fileDest = fopen($gzFilePath, 'w');

        event(new SnapshotStatus($this, 'Downloading snapshot...'));

        if (! file_exists($this->disk->path($this->fileName))) {
            while (feof($stream) !== true) {
                fwrite($fileDest, gzread($stream, self::STREAM_BUFFER_SIZE));
            }
        }

        $this->disk = Storage::disk('local');

        return $gzFilePath;
    }

    public function delete()
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
        event(new SnapshotStatus($this, 'Dropping all current database tables...'));

        DB::connection(DB::getDefaultConnection())
            ->getSchemaBuilder()
            ->dropAllTables();

        DB::reconnect();
    }
}
