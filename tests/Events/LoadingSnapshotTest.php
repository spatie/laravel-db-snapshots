<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Spatie\DbSnapshots\Events\LoadingSnapshot;

test('loading a snapshot fires the loading snapshot event', function () {
    Event::fake();

    Artisan::call('snapshot:load', ['name' => 'snapshot2']);

    Event::assertDispatched(LoadingSnapshot::class, function (LoadingSnapshot $event) {
        return $event->snapshot->fileName === 'snapshot2.sql';
    });
});
