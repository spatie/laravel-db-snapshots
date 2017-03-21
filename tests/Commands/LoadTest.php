<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Artisan;
use Spatie\DbSnapshots\Test\TestCase;
//use Mockery as m;

class LoadTest extends TestCase
{
    /** @var \Spatie\DbSnapshots\Commands\Delete|m\Mock */
    protected $command;

    public function setUp()
    {
        parent::setUp();

        //$this->command = m::mock('Spatie\DbSnapshots\Commands\Load[choice]');

        $this->app->bind('command.monitor:load', function () {
            return $this->command;
        });
    }

    /** @test */
    public function it_can_load_a_snapshot()
    {
        $this->assertSnapshotNotLoaded('snapshot2');

        $this->command
            ->shouldReceive('confirm')
            ->once()
            ->with('/Which snapshot/')
            ->andReturn('snapshot2');

        Artisan::call('snapshots:load');

        $this->assertSnapshotLoaded('snapshot2');
    }

    /** @test */
    public function it_can_load_a_snapshot_with_a_given_name()
    {
        $this->assertSnapshotNotLoaded('snapshot2');

        Artisan::call('snapshots:load', ['name' => 'snapshot2']);

        $this->assertSnapshotLoaded('snapshot2');
    }

    protected function assertSnapshotLoaded($snapshotName)
    {
        $this->assertEquals(
            $snapshotName,
            $this->getNameOfLoadedSnapshot(),
            "Failed to assert that `{$snapshotName}` is loaded. Current snapshot: `{$this->getNameOfLoadedSnapshot()}`"
        );
    }

    protected function assertSnapshotNotLoaded($snapshotName)
    {
        $this->assertNotEquals(
            $snapshotName,
            $this->getNameOfLoadedSnapshot(),
            "Failed to assert that `{$snapshotName}` was not loaded."
        );
    }

    protected function getNameOfLoadedSnapshot(): string
    {
        $result = DB::select('select `name` from models;');

        return count($result) ? $result[0]->name : '';
    }

}