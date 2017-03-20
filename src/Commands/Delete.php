<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;
use Spatie\DbSnapshots\Commands\Concerns\AsksForSnapshotName;
use Spatie\DbSnapshots\SnapshotRepository;

class Delete extends Command
{
    use AsksForSnapshotName;

    protected $signature = 'snapshots:delete {name?}';

    protected $description = 'Delete a snapshot.';

    public function handle()
    {
        $name = $this->argument('name') ?: $this->askForSnapshotName();

        $snapshot = app(SnapshotRepository::class)->getByName($name);

        $snapshot->delete();

        $this->comment("Snapshot `{$snapshot->name}` deleted!");
    }
}