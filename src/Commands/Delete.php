<?php

namespace Spatie\DbSnapshots\Commands;

use Illuminate\Console\Command;
use Spatie\DbSnapshots\SnapshotRepository;
use Spatie\DbSnapshots\Commands\Concerns\AsksForSnapshotName;

class Delete extends Command
{
    use AsksForSnapshotName;

    protected $signature = 'snapshot:delete {name?}';

    protected $description = 'Delete a snapshot.';

    public function handle()
    {
        $snapShots = app(SnapshotRepository::class)->getAll();

        if (app(SnapshotRepository::class)->getAll()->isEmpty()) {
            $this->warn('No snapshots found. Run `snapshot:create` to create snapshots.');

            return;
        }

        $name = $this->argument('name') ?: $this->askForSnapshotName();

        $snapshot = app(SnapshotRepository::class)->findByName($name);

        if (! $snapshot) {
            $this->warn("Snapshot `{$name}` does not exist!");

            return;
        }

        $snapshot->delete();

        $this->info("Snapshot `{$snapshot->name}` deleted!");
    }
}
