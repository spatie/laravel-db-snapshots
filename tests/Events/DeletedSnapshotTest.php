<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Artisan;
use Event;
use Spatie\DbSnapshots\Events\DeletedSnapshot;
use Spatie\DbSnapshots\Test\TestCase;

class DeletedSnapshotTest extends TestCase
{
    /** @test */
    public function after_the_snapshot_has_been_deleted_the_deletedsnapshot_event_will_be_fired()
    {
        Event::fake();

        Artisan::call('snapshot:delete', ['name' => 'snapshot2']);

        Event::assertDispatched(DeletedSnapshot::class, function (DeletedSnapshot $event) {
            return $event->fileName === 'snapshot2.sql';
        });
    }
}
