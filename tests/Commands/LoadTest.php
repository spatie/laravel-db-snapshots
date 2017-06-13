<?php

namespace Spatie\DbSnapshots\Commands\Test;

use DB;
use Mockery as m;
use Spatie\DbSnapshots\Test\TestCase;
use Illuminate\Support\Facades\Artisan;

class LoadTest extends TestCase
{
    /** @var \Spatie\DbSnapshots\Commands\Delete|m\Mock */
    protected $command;

    public function setUp()
    {
        parent::setUp();

        $this->command = m::mock('Spatie\DbSnapshots\Commands\Load[choice]');

        $this->app->bind('command.snapshot:load', function () {
            return $this->command;
        });
    }

    /** @test */
    public function it_can_load_a_snapshot()
    {
        $this->assertSnapshotNotLoaded('snapshot2');

        $this->command
            ->shouldReceive('choice')
            ->once()
            ->andReturn('snapshot2');

        Artisan::call('snapshot:load');

        $this->assertSnapshotLoaded('snapshot2');
    }

    /** @test */
    public function it_can_load_a_snapshot_with_a_given_name()
    {
        $this->assertSnapshotNotLoaded('snapshot2');

        Artisan::call('snapshot:load', ['name' => 'snapshot2']);

        $this->assertSnapshotLoaded('snapshot2');
    }

    /** @test */
    public function it_can_load_a_snapshot_with_connection_option()
    {
        $this->assertSnapshotNotLoaded('snapshot2');

        Artisan::call('snapshot:load', ['name' => 'snapshot2', '--connection' => 'testing']);

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
