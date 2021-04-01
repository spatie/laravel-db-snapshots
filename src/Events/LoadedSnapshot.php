<?php

namespace Spatie\DbSnapshots\Events;

use Spatie\DbSnapshots\Snapshot;

class LoadedSnapshot
{
    public function __construct(
        public Snapshot $snapshot,
    ) {
        //
    }
}
