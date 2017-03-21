<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use \Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Facades\DB;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\MigrateFresh\TableDroppers\TableDropper;

class Snapshot
{
    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    public $disk;

    /** @var string */
    public $fileName;

    /** @var string */
    public $name;

    public function __construct(Disk $disk, string $fileName)
    {
        $this->disk = $disk;

        $this->fileName = $fileName;

        $this->name = pathinfo($fileName, PATHINFO_FILENAME);
    }

    public function load()
    {
        event(new LoadingSnapshot($this));

        $tableDropper = $this->getTableDropper();

        $tableDropper->dropAllTables();

        $dbDumpContents = $this->disk->get($this->fileName);

        DB::statement($dbDumpContents);

        event(new LoadedSnapshot($this));
    }

    public function delete()
    {
        event(new DeletingSnapshot($this));

        $this->disk->delete($this->fileName);

        event(new DeletedSnapshot($this->name, $this->disk));
    }

    public function size(): int
    {
        return $this->disk->size($this->fileName);
    }

    public function createdAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->disk->lastModified($this->fileName));
    }

    /**
     * TO DO: create factory in table-dropper package
     *
     * @return mixed
     */
    protected function getTableDropper(): TableDropper
    {
        $driverName = DB::getDriverName();

        $dropperClass = '\\Spatie\\MigrateFresh\\TableDroppers\\' . ucfirst($driverName);

        if (!class_exists($dropperClass)) {
            throw CannotDropTables::unsupportedDbDriver($driverName);
        }

        return new $dropperClass;
    }
}