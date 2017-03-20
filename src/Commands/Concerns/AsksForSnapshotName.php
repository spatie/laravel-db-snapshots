<?php

namespace Spatie\DbSnapshots\Commands\Concerns;

trait AsksForSnapshotName
{
    protected function askForSnapshotName(): string
    {
        $snapShots = app(SnapshotRepository::class)->getAll();

        $names = $snapShots->map(function (Snapshot $snapshot) {
            return $snapshot->name;
        })->toArray();

        return $this->choice('Which snapshot?', $names, 0);
    }
}