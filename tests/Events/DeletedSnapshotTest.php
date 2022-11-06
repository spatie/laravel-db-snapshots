<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\DbSnapshots\Events\DeletedSnapshot;

test('after a snapshot has been deleted the deleted snapshot event will be fired', function () {
    Event::fake();

    Artisan::call('snapshot:delete', ['name' => 'snapshot2']);

    Event::assertDispatched(DeletedSnapshot::class, function (DeletedSnapshot $event) {
        return $event->fileName === 'snapshot2.sql';
    });
});
