<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Artisan;
use Event;
use Spatie\DbSnapshots\Events\CreatingSnapshot;
use Spatie\DbSnapshots\Test\TestCase;

class CreatingSnapshotTest extends TestCase
{
    /** @test */
    public function creating_a_snapshot_fires_the_creating_snapshot_event()
    {
        Event::fake();

        Artisan::call('snapshot:create', ['name' => 'my-snapshot']);

        Event::assertDispatched(CreatingSnapshot::class, function (CreatingSnapshot $event) {
            return $event->fileName === 'my-snapshot.sql';
        });
    }
}
