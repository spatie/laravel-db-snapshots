<?php

namespace Spatie\DbSnapshots\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class CreatingSnapshot
{
    /** @var string */
    public $fileName;

    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    public $disk;

    /** @var string */
    public $connectionName;

    public function __construct(string $fileName, FilesystemAdapter $disk, string $connectionName)
    {
        $this->fileName = $fileName;

        $this->disk = $disk;

        $this->connectionName = $connectionName;
    }
}
