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
    public function __construct(
        protected DbDumperFactory $dumperFactory,
        protected Factory $filesystemFactory,
    ) {
        //
    }

    public function create(string $snapshotName, string $diskName, string $connectionName, bool $compress = false, ?array $tables = null, ?array $exclude = null, array $extraOptions = []): Snapshot
    {
        $disk = $this->getDisk($diskName);

        $fileName = $snapshotName . '.sql';
        $fileName = pathinfo($fileName, PATHINFO_BASENAME);

        if ($compress) {
            $fileName .= '.gz';
        }

        event(new CreatingSnapshot(
            $fileName,
            $disk,
            $connectionName,
            $tables,
            $exclude,
            $extraOptions
        ));

        $this->createDump($connectionName, $fileName, $disk, $compress, $tables, $exclude, $extraOptions);

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

    protected function createDump(string $connectionName, string $fileName, FilesystemAdapter $disk, bool $compress = false, ?array $tables = null, ?array $exclude = null, array $extraOptions = []): void
    {
        $directory = (new TemporaryDirectory(config('db-snapshots.temporary_directory_path')))->create();

        $dumpPath = $directory->path($fileName);

        $dbDumper = $this->getDbDumper($connectionName);

        if ($compress) {
            $dbDumper->useCompressor(new GzipCompressor());
        }

        if (is_array($tables)) {
            $dbDumper->includeTables($tables);
        }

        if (is_array($exclude)) {
            $dbDumper->excludeTables($exclude);
        }

        foreach ($extraOptions as $extraOption) {
            $dbDumper->addExtraOption($extraOption);
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
