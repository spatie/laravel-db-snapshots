<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Spatie\MigrateFresh\Events\DroppedTables;
use Spatie\MigrateFresh\Events\DroppingTables;
use Spatie\MigrateFresh\TableDroppers\TableDropper;
use Spatie\MigrateFresh\Exceptions\CannotDropTables;

class Load extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'db-snapshots:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load a previously dumped database.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->info('Dropping all tables...');

        event(new DroppingTables());
        $this->getTableDropper()->dropAllTables();
        event(new DroppedTables());

        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        if ($this->option('seed')) {
            $this->info('Running seeders...');
            $this->call('db:seed', ['--force' => true]);
        }

        $this->comment('All done!');
    }

    public function getTableDropper(): TableDropper
    {
        $driverName = DB::getDriverName();

        $dropperClass = '\\Spatie\\MigrateFresh\\TableDroppers\\'.ucfirst($driverName);

        if (! class_exists($dropperClass)) {
            throw CannotDropTables::unsupportedDbDriver($driverName);
        }

        return new $dropperClass;
    }
}
