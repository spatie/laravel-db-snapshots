<?php

namespace Spatie\DbSnapshots\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class CreatingSnapshot
{
    public function __construct(
        public string $fileName,
        public FilesystemAdapter $disk,
        public string $connectionName,
        public ?array $tables = null,
        public ?array $exclude = null,
        public ?array $extraOptions = null
    ) {
        //
    }
}
