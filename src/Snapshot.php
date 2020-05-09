<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use \Exception;
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

    public function loadAsync(string $connectionName = null)
    {
        $dbDumpContents = $this->disk->get($this->fileName);

        if ($this->compressionExtension === 'gz') {
            $dbDumpContents = gzdecode($dbDumpContents);
        }

        DB::connection($connectionName)->unprepared($dbDumpContents);
    }

    public function loadStream(string $connectionName = null)
    {
        $dumpFilePath = $this->compressionExtension === 'gz' ? $this->streamDecompress() : $this->disk->path($this->fileName);
        $this->streamFileIntoDB($dumpFilePath, $connectionName);
    }

    public function streamFileIntoDB($path, string $connectionName = null)
    {
        if ($connectionName !== null) {
            DB::setDefaultConnection($connectionName);
        }

        $tmpLine = '';

        $lines = file($path);
        $errors = [];

        foreach ($lines as $line) {

            // Skip it if line is a comment
            if (substr($line, 0, 2) === '--' || trim($line) == '') {
                continue;
            }

            // Add this line to the current segment
            $tmpLine .= $line;

            // If the line ends with a semicolon, it is the end of the query - run it
            if (substr(trim($line), -1, 1) === ';') {
                try {
                    DB::connection($connectionName)->unprepared($tmpLine);
                } catch (Exception $e) {
                    $errors[] = [
                        'query'   => $tmpLine,
                        'message' => $e->getMessage(),
                    ];
                }

                $tmpLine = '';
            }
        }

        if (empty($errors)) {
            return true;
        }

        return $errors;
    }

    public function streamDecompress()
    {
        $stream      = $this->disk->readStream($this->fileName);
        $directory   = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))->create();
        $loadPath    = $directory->path('temp-load.tmp');
        $gzPath      = $loadPath.'.gz';
        $sqlPath     = $loadPath.'.sql';
        $fileDest    = fopen($gzPath, 'w');
        $buffer_size = 4096;

        if (!file_exists($this->disk->path($this->fileName))) {
            while (feof($stream) !== true) {
                fwrite($fileDest, fread($stream, $buffer_size));
            }
        }

        $fileSource = gzopen($gzPath, 'rb');
        $fileDest = fopen($sqlPath, 'w');

        while (feof($fileSource) !== true) {
            fwrite($fileDest, gzread($fileSource, $buffer_size));
        }

        fclose($stream);
        fclose($fileDest);

        $this->disk = Storage::disk('local');

        return $sqlPath;
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
        DB::connection(DB::getDefaultConnection())
            ->getSchemaBuilder()
            ->dropAllTables();

        DB::reconnect();
    }
}
