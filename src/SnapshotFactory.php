<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use Spatie\DbDumper\DbDumper;
use Spatie\DbSnapshots\Exceptions\CannotCreateDisk;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class SnapshotFactory
{
    /** @var \Spatie\DbSnapshots\DbDumperFactory */
    protected $dumperFactory;

    /** @var \Illuminate\Contracts\Filesystem\Factory */
    protected $filesystemFactory;

    public function __construct(DbDumperFactory $dumperFactory, Factory $filesystemFactory)
    {
        $this->dumperFactory = $dumperFactory;

        $this->filesystemFactory = $filesystemFactory;
    }

    public function create(string $diskName, string $connectionName, string $snapshotName): Snapshot
    {
        $directory = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))->create();

        $fileName = $snapshotName . '.sql';

        $dumpPath = $directory->path($fileName);

        $this->getDbDumper($connectionName)->dumpToFile($dumpPath);

        $disk = $this->getDisk($diskName);

        $file = fopen($dumpPath, 'r');

        $disk->put($fileName, $file);

        fclose($file);

        $directory->delete();

        return new Snapshot($disk, $fileName);
    }

    protected function getDisk(string $diskName): FilesystemAdapter
    {
        if(is_null(config("filesystems.disks.{$diskName}"))) {
            throw CannotCreateDisk::diskNotDefined($diskName);
        }

        return $this->filesystemFactory->disk($diskName);
    }

    protected function getDbDumper(string $connectionName): DbDumper
    {
        $factory = $this->dumperFactory;

        return $factory::createForConnection($connectionName);
    }
}