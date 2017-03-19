<?php

namespace Spatie\DbSnapshots\Commands;

use DB;
use Illuminate\Console\Command;

class Delete extends Command
{
    protected $signature = 'snapshots:delete --disk';

    protected $description = 'Delete a snapshot.';

    public function handle()
    {
        $this->comment('All done!');
    }
}