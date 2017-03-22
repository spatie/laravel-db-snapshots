<?php

namespace Spatie\DbSnapshots\Events;

use Spatie\DbSnapshots\Snapshot;

class LoadingSnapshot
{
    /** @var \Spatie\DbSnapshots\Snapshot */
    public $snapshot;

    public function __construct(Snapshot $snapshot)
    {
        $this->snapshot = $snapshot;
    }
}
