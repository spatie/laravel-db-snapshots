<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;

class ListSnapshots extends Command
{
    protected $signature = 'snapshots:delete --disk';

    protected $description = 'List all the snapshots.';

    public function handle()
    {
        $this->comment('All done!');
    }
}