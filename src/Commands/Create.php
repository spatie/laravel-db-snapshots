<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\MigrateFresh\Events\DroppedTables;
use Spatie\MigrateFresh\Events\DroppingTables;
use Spatie\MigrateFresh\TableDroppers\TableDropper;
use Spatie\MigrateFresh\Exceptions\CannotDropTables;

class Create extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'db-snapshots:create {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new snapshot.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Creating new snapshot...');

        app(\Spatie\DbSnapshots\SnapshotFactory)


        $this->comment('All done!');
    }
}
