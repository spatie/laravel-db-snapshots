<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Illuminate\Support\Facades\Artisan;
use Spatie\DbSnapshots\Test\TestCase;

class ListSnapshotsTest extends TestCase
{
    /** @test */
    public function it_can_list_all_snapshots()
    {
        Artisan::call('snapshot:list');

        $this->seeInConsoleOutput(['snapshot1', 'snapshot2', 'snapshot3']);
    }
}
