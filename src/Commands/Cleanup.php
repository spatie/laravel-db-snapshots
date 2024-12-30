<?php

namespace Spatie\DbSnapshots\Commands;

use Illuminate\Console\Command;
use Spatie\DbSnapshots\Snapshot;
use Spatie\DbSnapshots\SnapshotRepository;

class Cleanup extends Command
{
    protected $signature = 'snapshot:cleanup {--keep=}';

    protected $description = 'Specify how many snapshots to keep and delete the rest';

    public function handle()
    {
        $snapshots = app(SnapshotRepository::class)->getAll();

        $keep = $this->option('keep');

        if (! $keep && $keep !== '0') {
            $this->warn('No value for option --keep.');

            return;
        }

        $snapshots->splice($keep)->each(fn (Snapshot $snapshot) => $snapshot->delete());
    }
}
