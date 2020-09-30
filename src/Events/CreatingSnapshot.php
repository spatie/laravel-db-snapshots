<?php

namespace Spatie\DbSnapshots\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class CreatingSnapshot
{
    public string $fileName;

    public FilesystemAdapter $disk;

    public string $connectionName;

    public function __construct(string $fileName, FilesystemAdapter $disk, string $connectionName)
    {
        $this->fileName = $fileName;

        $this->disk = $disk;

        $this->connectionName = $connectionName;
    }
}
