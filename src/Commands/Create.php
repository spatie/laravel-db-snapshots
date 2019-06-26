<?php

namespace Spatie\DbSnapshots\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\DbSnapshots\Helpers\Format;
use Spatie\DbSnapshots\SnapshotFactory;

class Create extends Command
{
    protected $signature = 'snapshot:create {name?} {--connection=} {--compress}';

    protected $description = 'Create a new snapshot.';

    public function handle()
    {
        $this->info('Creating new snapshot...');

        $connectionName = $this->option('connection')
            ?: config('db-snapshots.default_connection')
            ?? config('database.default');

        $snapshotName = $this->argument('name') ?: Carbon::now()->format('Y-m-d_H-i-s');

        $compress = $this->option('compress') || config('db-snapshots.compress', false);

        $snapshot = app(SnapshotFactory::class)->create(
            $snapshotName,
            config('db-snapshots.disk'),
            $connectionName,
            $compress
        );

        $size = Format::humanReadableSize($snapshot->size());

        $this->info("Snapshot `{$snapshotName}` created (size: {$size})");
    }
}
