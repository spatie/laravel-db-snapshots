<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Artisan;
use Event;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\DbSnapshots\Test\TestCase;

class LoadingSnapshotTest extends TestCase
{
    /** @test */
    public function loading_a_snapshot_fires_the_loading_snapshot_event()
    {
        Event::fake();

        Artisan::call('snapshot:load', ['name' => 'snapshot2']);

        Event::assertDispatched(LoadingSnapshot::class, function (LoadingSnapshot $event) {
            return $event->snapshot->fileName === 'snapshot2.sql';
        });
    }
}
