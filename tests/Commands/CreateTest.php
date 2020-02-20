<?php

namespace Spatie\DbSnapshots\Commands\Test;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Spatie\DbSnapshots\Test\TestCase;

class CreateTest extends TestCase
{
    /** @test */
    public function it_can_create_a_snapshot_without_a_specific_name()
    {
        Artisan::call('snapshot:create');

        $fileName = Carbon::now()->format('Y-m-d_H-i-s').'.sql';

        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
    }

    /** @test */
    public function it_can_create_a_snapshot_with_specific_name()
    {
        Artisan::call('snapshot:create', ['name' => 'test']);

        $this->assertFileOnDiskPassesRegex('test.sql', '/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
    }

    /** @test */
    public function it_can_create_a_compressed_snapshot_from_cli_param()
    {
        Artisan::call('snapshot:create', ['--compress' => true]);

        $fileName = Carbon::now()->format('Y-m-d_H-i-s').'.sql.gz';

        $this->disk->assertExists($fileName);

        $this->assertNotEmpty(gzdecode($this->disk->get($fileName)));
    }

    /** @test */
    public function it_can_create_a_compressed_snapshot_from_config()
    {
        $this->app['config']->set('db-snapshots.compress', true);

        Artisan::call('snapshot:create');

        $fileName = Carbon::now()->format('Y-m-d_H-i-s').'.sql.gz';

        $this->disk->assertExists($fileName);

        $this->assertNotEmpty(gzdecode($this->disk->get($fileName)));
    }
}
