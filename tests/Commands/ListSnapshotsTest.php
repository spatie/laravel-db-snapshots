<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Illuminate\Support\Facades\Artisan;
use Spatie\DbSnapshots\Test\TestCase;
use Mockery as m;

class ListSnapshotsTest extends TestCase
{
    /** @test */
    public function it_can_list_all_snapshots()
    {
        Artisan::call('snapshots:list');

        $this->seeInConsoleOutput(['snapshot1', 'snapshot2', 'snapshot3']);
    }
}