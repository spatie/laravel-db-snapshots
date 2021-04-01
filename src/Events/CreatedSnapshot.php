<?php

namespace Spatie\DbSnapshots\Events;

use Spatie\DbSnapshots\Snapshot;

class CreatedSnapshot
{
    public function __construct(
        public Snapshot $snapshot
    ) {
        //
    }
}
