<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Spatie\DbDumper\DbDumper;
use \Illuminate\Filesystem\FilesystemAdapter as Disk;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class SnapshotFactory
{
    public static function create(DbDumper $dbDumper, Disk $disk, string $name = null): Snapshot
    {
        $directory = new TemporaryDirectory(config('db-snapshots.temporary_directory_path'));

        $fileName = $name ?? Carbon::createFromFormat('Y-m-d H:i:s') . '.sql';

        $dumpPath = $directory->path($fileName);

        $dbDumper->dumpToFile($dumpPath);

        //TO DO: avoid opening file, might be problem for big dumps
        $disk->put($fileName, file_get_contents($dumpPath));

        $directory->delete();

        return new Snapshot($disk, $fileName);
    }
}