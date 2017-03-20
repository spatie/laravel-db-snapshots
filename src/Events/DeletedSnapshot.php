<?php

namespace Spatie\DbSnapshots\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class DeletedSnapshot
{
    /** @var string */
    public $snapshotName;

    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    public $disk;

    public function __construct(string $snapshotName, FilesystemAdapter $disk)
    {
        $this->snapshotName = $snapshotName;

        $this->disk = $disk;
    }
}