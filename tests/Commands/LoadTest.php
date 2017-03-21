<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Spatie\DbSnapshots\Test\TestCase;

class LoadTest extends TestCase
{
    /** @test */
    public function it_can_create_a_snapshot()
    {
        Artisan::call('snapshots:create');

        $fileName = Carbon::now()->format('Y-m-d H:i:s') . '.sql';

        $this->assertFileOnDiskContains($fileName, 'CREATE TABLE "models"');
    }

    /** @test */
    public function it_can_create_a_snapshot_with_specific_name()
    {
        Artisan::call('snapshots:create', ['name' => 'test']);

        $this->assertFileOnDiskContains('test.sql', 'CREATE TABLE "models"');
    }
}