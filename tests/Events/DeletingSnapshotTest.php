<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Artisan;
use Event;
use Spatie\DbSnapshots\Events\DeletingSnapshot;
use Spatie\DbSnapshots\Test\TestCase;

class DeletingSnapshotTest extends TestCase
{
    /** @test */
    public function deleting_a_snapshot_fires_the_deleting_snapshot_event()
    {
        Event::fake();

        Artisan::call('snapshot:delete', ['name' => 'snapshot2']);

        Event::assertDispatched(DeletingSnapshot::class, function (DeletingSnapshot $event) {
            return $event->snapshot->fileName === 'snapshot2.sql';
        });
    }
}
