<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use \Illuminate\Filesystem\FilesystemAdapter as Disk;
use Illuminate\Support\Facades\DB;
use Spatie\MigrateFresh\TableDroppers\TableDropper;

class Snapshot
{
    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    protected $disk;

    /** @var string */
    protected $fileName;

    /** @var string */
    public $name;

    public function __construct(Disk $disk, string $fileName)
    {
        $this->disk = $disk;

        $this->fileName = $fileName;

        $this->name = $fileName;
    }

    public function load()
    {
        $tableDropper = $this->getTableDropper();

        $tableDropper->dropAllTables();

        $dbDumpContents = $this->disk->get($this->fileName);

        DB::statement($dbDumpContents);
    }

    public function delete()
    {
        $this->disk->delete($this->fileName);
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

    public function size(): int
    {
        return $this->disk->size($this->fileName);
    }

    public function createdAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->disk->lastModified($this->fileName));
    }
}