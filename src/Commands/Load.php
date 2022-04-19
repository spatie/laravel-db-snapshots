<?php

namespace Spatie\DbSnapshots\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\DbSnapshots\Commands\Concerns\AsksForSnapshotName;
use Spatie\DbSnapshots\SnapshotRepository;

class Load extends Command
{
    use AsksForSnapshotName;
    use ConfirmableTrait;

    protected $signature = 'snapshot:load {name?} {--connection=} {--force} {--stream} --disk {--latest} {--drop-tables=1}';

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

        $useLatestSnapshot = $this->option('latest') ?: false;

        $name = $useLatestSnapshot
            ? $snapShots->first()->name
            : ($this->argument('name') ?: $this->askForSnapshotName());

        /** @var \Spatie\DbSnapshots\Snapshot $snapshot */
        $snapshot = app(SnapshotRepository::class)->findByName($name);

        if (! $snapshot) {
            $this->warn("Snapshot `{$name}` does not exist!");

            return;
        }

        if ($this->option('stream') ?: false) {
            $snapshot->useStream();
        }

        $snapshot->load($this->option('connection'), (bool) $this->option('drop-tables'));

        $this->info("Snapshot `{$name}` loaded!");
    }
}
