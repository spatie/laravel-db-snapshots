<?php

namespace Spatie\DbSnapshots\Events;

use Spatie\DbSnapshots\Snapshot;

class LoadingSnapshot
{
    public function __construct(
        public Snapshot $snapshot,
    ) {
        //
    }
}
