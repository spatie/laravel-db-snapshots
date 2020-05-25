<?php

namespace Spatie\DbSnapshots\Events;

use Spatie\DbSnapshots\Snapshot;

class SnapshotStatus
{
    /** @var \Spatie\DbSnapshots\Snapshot */
    public $snapshot;

    /** @var string */
    public $status;

    public function __construct(Snapshot $snapshot, string $status)
    {
        $this->snapshot = $snapshot;
        $this->status = $status;
    }
}
