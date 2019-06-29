<?php

namespace Spatie\DbSnapshots\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\DbSnapshots\SnapshotRepository;
use Spatie\DbSnapshots\Commands\Concerns\AsksForSnapshotName;

class Load extends Command
{
    use AsksForSnapshotName;
    use ConfirmableTrait;

    protected $signature = 'snapshot:load {name?} {--connection=} {--force} --disk';

    protected $description = 'Load up a snapshot.';

    public function handle()
    {
        $snapShots = app(SnapshotRepository::class)->getAll();

        if ($snapShots->isEmpty()) {
            $this->warn('No snapshots found. Run `snapshot:create` first to create snapshots.');

            return;
        }

        if (! $this->confirmToProceed()) {
            return;
        }

        $name = $this->argument('name') ?: $this->askForSnapshotName();

        $snapshot = app(SnapshotRepository::class)->findByName($name);

        if (! $snapshot) {
            $this->warn("Snapshot `{$name}` does not exist!");

            return;
        }

        $snapshot->load($this->option('connection'));

        $this->info("Snapshot `{$name}` loaded!");
    }
}
