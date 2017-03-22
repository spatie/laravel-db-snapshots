<?php

namespace Spatie\DbSnapshots;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\MigrateFresh\TableDropperFactory;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Illuminate\Filesystem\FilesystemAdapter as Disk;

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

        $this->dropAllCurrentTables();

        $dbDumpContents = $this->disk->get($this->fileName);

        foreach (explode(PHP_EOL, $dbDumpContents) as $statement) {
            DB::statement($statement);
        }

        event(new LoadedSnapshot($this));
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
        $tableDropper = TableDropperFactory::create(DB::getDriverName());

        $tableDropper->dropAllTables();

        DB::reconnect();
    }
}
