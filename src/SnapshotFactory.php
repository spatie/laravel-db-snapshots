<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use Spatie\DbDumper\DbDumper;
use \Illuminate\Filesystem\FilesystemAdapter as Disk;
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

    public function create(string $diskName, string $connectionName, string $snapshotName = null): Snapshot
    {
        $directory = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))->create();

        $fileName = empty($snapshotName) ? Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now()) . '.sql': $snapshotName . '.sql';

        $dumpPath = $directory->path($fileName);

        $this->getDbDumper($connectionName)->dumpToFile($dumpPath);


        $disk = $this->getDisk($diskName);
        //TO DO: avoid opening file, might be problem for big dumps
        $disk->put($fileName, file_get_contents($dumpPath));

        $directory->delete();

        return new Snapshot($disk, $fileName);
    }

    protected function getDisk(string $diskName): FilesystemAdapter
    {
        return $this->filesystemFactory->disk($diskName);
    }

    protected function getDbDumper(string $connectionName): DbDumper
    {
        $factory = $this->dumperFactory;

        return $factory::createForConnection($connectionName);
    }
}