<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;
use Spatie\DbSnapshots\Snapshot;
use Spatie\DbSnapshots\SnapshotRepository;

class Delete extends Command
{
    protected $signature = 'snapshots:delete {name?}';

    protected $description = 'Delete a snapshot.';

    public function handle()
    {
        $name = $this->argument('name') ?: $this->askForSnapshotName();

        $snapshot = app(SnapshotRepository::class)->getByName($name);

        $snapshot->delete();

        $this->comment("Snapshot `{$snapshot->name}` deleted!");
    }

    protected function askForSnapshotName(): string
    {
        $snapShots = app(SnapshotRepository::class)->getAll();

        $names = $snapShots->map(function (Snapshot $snapshot) {
            return $snapshot->name;
        })->toArray();

        return $this->choice('Which snapshot?', $names, 0);
    }
}