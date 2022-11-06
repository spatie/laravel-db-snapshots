<?php

use Illuminate\Support\Facades\Artisan;
use Mockery as m;

beforeEach(function () {
    $this->command = m::mock('Spatie\DbSnapshots\Commands\Delete[choice]');

    $this->app->bind('command.snapshot:delete', function () {
        return $this->command;
    });
});

it('can delete a snapshot', function () {
    $this->disk->assertExists('snapshot2.sql');

    $this->command
        ->shouldReceive('choice')
        ->once()
        ->andReturn('snapshot2');

    Artisan::call('snapshot:delete');

    $this->disk->assertMissing('snapshot2.sql');
});

it('can delete a snapshot with a specific name', function () {
    $this->disk->assertExists('snapshot2.sql');

    Artisan::call('snapshot:delete', ['name' => 'snapshot2']);

    $this->disk->assertMissing('snapshot2.sql');
});
