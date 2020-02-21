<?php

namespace Spatie\DbSnapshots;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\DbDumper;
use Spatie\DbSnapshots\Events\CreatedSnapshot;
use Spatie\DbSnapshots\Events\CreatingSnapshot;
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

    public function create(string $snapshotName, string $diskName, string $connectionName, bool $compress = false): Snapshot
    {
        $disk = $this->getDisk($diskName);

        $fileName = $snapshotName.'.sql';
        $fileName = pathinfo($fileName, PATHINFO_BASENAME);

        if ($compress) {
            $fileName .= '.gz';
        }

        event(new CreatingSnapshot(
            $fileName,
            $disk,
            $connectionName
        ));

        $this->createDump($connectionName, $fileName, $disk, $compress);

        $snapshot = new Snapshot($disk, $fileName);

        event(new CreatedSnapshot($snapshot));

        return $snapshot;
    }

    protected function getDisk(string $diskName): FilesystemAdapter
    {
        if (is_null(config("filesystems.disks.{$diskName}"))) {
            throw CannotCreateDisk::diskNotDefined($diskName);
        }

        return $this->filesystemFactory->disk($diskName);
    }

    protected function getDbDumper(string $connectionName): DbDumper
    {
        $factory = $this->dumperFactory;

        return $factory::createForConnection($connectionName);
    }

    protected function createDump(string $connectionName, string $fileName, FilesystemAdapter $disk, bool $compress = false)
    {
        $directory = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))->create();

        $dumpPath = $directory->path($fileName);

        $dbDumper = $this->getDbDumper($connectionName);

        if ($compress) {
            $dbDumper->useCompressor(new GzipCompressor());
        }

        $dbDumper->dumpToFile($dumpPath);

        $file = fopen($dumpPath, 'r');

        $disk->put($fileName, $file);

        if (is_resource($file)) {
            fclose($file);
        }

        gc_collect_cycles();

        $directory->delete();
    }
}
