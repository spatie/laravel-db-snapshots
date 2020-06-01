<?php

namespace Spatie\DbSnapshots\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class DeletedSnapshot
{
    public string $fileName;

    public FilesystemAdapter $disk;

    public function __construct(string $fileName, FilesystemAdapter $disk)
    {
        $this->fileName = $fileName;

        $this->disk = $disk;
    }
}
