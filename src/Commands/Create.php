<?php

namespace Spatie\DbSnapshots\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\DbSnapshots\Helpers\Format;
use Spatie\DbSnapshots\SnapshotFactory;

class Create extends Command
{
    protected $signature = 'snapshot:create {name?} {--connection=} {--compress} {--table=*} {--exclude=*}';

    protected $description = 'Create a new snapshot.';

    public function handle()
    {
        $this->info('Creating new snapshot...');

        $connectionName = $this->option('connection')
            ?: config('db-snapshots.default_connection')
            ?? config('database.default');

        $snapshotName = $this->argument('name') ?? Carbon::now()->format('Y-m-d_H-i-s');

        $compress = $this->option('compress') || config('db-snapshots.compress', false);

        $tables = $this->option('table') ?: config('db-snapshots.tables', null);
        $tables = is_string($tables) ? explode(',', $tables) : $tables;

        if (is_null($tables)) {
            $exclude = $this->option('exclude') ?: config('db-snapshots.exclude', null);
            $exclude = is_string($exclude) ? explode(',', $exclude) : $exclude;
        } else {
            $exclude = null;
        }


        $snapshot = app(SnapshotFactory::class)->create(
            $snapshotName,
            config('db-snapshots.disk'),
            $connectionName,
            $compress,
            $tables,
            $exclude
        );

        $size = Format::humanReadableSize($snapshot->size());

        $this->info("Snapshot `{$snapshotName}` created (size: {$size})");
    }
}
