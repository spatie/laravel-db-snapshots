<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\DbSnapshots\Snapshot;
use Spatie\DbSnapshots\SnapshotRepository;

class Load extends Command
{
    use ConfirmableTrait;

    protected $signature = 'snapshots:load {name?} --disk';

    protected $description = 'Load up a snapshots.';

    public function handle()
    {
        $snapShots = app(SnapshotRepository::class)->getAll();

        if ($snapShots->isEmpty()) {
            $this->warn("No snapshots found. Run `snapshot:create` first to create snapshots.");

            return;
        }

        if (! $this->confirmToProceed()) {
            return;
        }

        $name = $this->argument('name') ?: $this->askForSnapshotName($snapShots);

        app(SnapshotRepository::class)->getByName($name)->load();

        $this->comment("Snapshot {$name} loaded");
    }

    /**
     * @param $snapShots
     * @return string
     */
    protected function askForSnapshotName($snapShots): string
    {
        $names = $snapShots->map(function (Snapshot $snapshot) {
            return $snapshot->name;
        })->toArray();

        $chosenName = $this->choice('Which snapshot?', $names, 0);
        return $chosenName;
    }
}