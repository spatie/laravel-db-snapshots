<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Artisan;
use Event;
use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Test\TestCase;

class LoadedSnapshotTest extends TestCase
{
    /** @test */
    public function after_a_snapshot_has_been_loaded_the_loaded_snapshot_event_will_be_fired()
    {
        Event::fake();

        Artisan::call('snapshot:load', ['name' => 'snapshot2']);

        Event::assertDispatched(LoadedSnapshot::class, function (LoadedSnapshot $event) {
            return $event->snapshot->fileName === 'snapshot2.sql';
        });
    }
}
