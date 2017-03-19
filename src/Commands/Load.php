<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

class Load extends Command
{
    use ConfirmableTrait;

    protected $signature = 'db-snapshots:load {name} --disk';

    protected $description = 'Load up a snapshots.';

    public function handle()
    {
        if (! $this->confirm()) {
            return;
        }

        $this->comment('All done!');
    }
}