<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\DbSnapshots\Helpers\Format;
use Spatie\DbSnapshots\SnapshotFactory;

class Create extends Command
{
    use ConfirmableTrait;

    protected $signature = 'snapshots:create {name?} {--connection}';

    protected $description = 'Create a new snapshot.';

    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->info('Creating new snapshot...');

        $connectionName = $this->option('connection')
            ?: config('db-snapshots.default_connection')
            ?? config('database.default');

        $snapshotName = $this->argument('name');

        $snapshot = app(SnapshotFactory::class)->create(
            config('db-snapshots.disk'),
            $connectionName,
            $snapshotName);

        $size = Format::humanReadableSize($snapshot->size());

        $this->info("Snapshot `{$snapshotName}` created (size: {$size})");

        $this->comment('All done!');
    }
}
