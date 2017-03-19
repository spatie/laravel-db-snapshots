<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\DbSnapshots\SnapshotFactory;

class Create extends Command
{
    use ConfirmableTrait;

    protected $signature = 'db-snapshots:create --name --disk --connection';

    protected $description = 'Create a new snapshot.';

    public function handle()
    {
        if (! $this->confirm()) {
            return;
        }

        $this->info('Creating new snapshot...');

        $diskName = $this->option('disk') ?? config('db-snapshots.disk');

        $connectionName = $this->option('connection')
            ?? config('db-snapshots.default_connection')
            ?? config('databases.default');

        $snapshotName = $this->option('name');

        $snapshot = SnapshotFactory::createForConnectionOnDisk($diskName, $connectionName, $snapshotName);

        $this->info("Snapshot created on disk {$diskName} (size: {$snapshot->size}");

        $this->comment('All done!');
    }
}
