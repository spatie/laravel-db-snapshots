<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Spatie\DbSnapshots\Test\TestCase;
use Event;
use Artisan;
use Spatie\DbSnapshots\Events\CreatingSnapshot;

class CreatingSnapshotTest extends TestCase
{
    /** @test */
    public function creating_a_snapshot_fires_the_creating_snapshot_event()
    {
        Event::fake();

        Artisan::call('snapshots:create', ['name' => 'my-snapshot']);

        Event::assertDispatched(CreatingSnapshot::class, function(CreatingSnapshot $event) {
            return $event->fileName === 'my-snapshot.sql';
        });
    }
}