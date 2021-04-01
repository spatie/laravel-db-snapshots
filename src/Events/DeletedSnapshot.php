<?php

namespace Spatie\DbSnapshots\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class DeletedSnapshot
{
    public function __construct(
        public string $fileName,
        public FilesystemAdapter $disk,
    ) {
        //
    }
}
