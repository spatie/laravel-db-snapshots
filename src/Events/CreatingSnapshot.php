<?php

namespace Spatie\DbSnapshots\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class CreatingSnapshot
{
    /** @var string */
    public $snapshotName;

    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    public $disk;

    /** @var string  */
    public $connectionName;

    public function __construct(string $snapshotName, FilesystemAdapter $disk, string $connectionName)
    {
        $this->snapshotName = $snapshotName;

        $this->disk = $disk;

        $this->connectionName = $connectionName;
    }
}