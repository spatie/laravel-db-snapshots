<?php

namespace Spatie\DbSnapshots;

use \Illuminate\Filesystem\FilesystemAdapter as Disk;

class Snapshot
{
    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    protected $disk;

    /** @var string */
    protected $fileName;

    public static function fromDb()
    {

    }

    public function __construct(Disk $disk, string $fileName)
    {
        $this->disk = $disk;

        $this->fileName = $fileName;
    }

    public function load()
    {

    }

    public function delete()
    {

    }
}