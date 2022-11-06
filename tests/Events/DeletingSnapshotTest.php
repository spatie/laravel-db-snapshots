<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\DbSnapshots\Events\DeletingSnapshot;

test('deleting a snapshot fires the deleting snapshot event', function () {
    Event::fake();

    Artisan::call('snapshot:delete', ['name' => 'snapshot2']);

    Event::assertDispatched(DeletingSnapshot::class, function (DeletingSnapshot $event) {
        return $event->snapshot->fileName === 'snapshot2.sql';
    });
});
