<?php

namespace Spatie\DbSnapshots\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class DeletedSnapshot
{
    /** @var string */
    public $fileName;

    /** @var \Illuminate\Filesystem\FilesystemAdapter */
    public $disk;

    public function __construct(string $fileName, FilesystemAdapter $disk)
    {
        $this->fileName = $fileName;

        $this->disk = $disk;
    }
}
