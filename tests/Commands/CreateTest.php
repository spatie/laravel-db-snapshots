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

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/');
        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/');
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

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql.gz';

        $this->disk->assertExists($fileName);

        $this->assertNotEmpty(gzdecode($this->disk->get($fileName)));
    }

    /** @test */
    public function it_can_create_a_compressed_snapshot_from_config()
    {
        $this->app['config']->set('db-snapshots.compress', true);

        Artisan::call('snapshot:create');

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql.gz';

        $this->disk->assertExists($fileName);

        $this->assertNotEmpty(gzdecode($this->disk->get($fileName)));
    }

    /** @test */
    public function it_can_create_a_snapshot_with_specific_tables_specified_in_the_command_options()
    {
        Artisan::call('snapshot:create', ['--table' => ['users', 'posts']]);

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/');
        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/');
        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
    }

    /** @test */
    public function it_can_create_a_snapshot_with_specific_tables_specified_in_the_command_options_as_a_string()
    {
        Artisan::call('snapshot:create', ['--table' => 'users,posts']);

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/');
        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/');
        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
    }

    /** @test */
    public function it_can_create_a_snapshot_with_specific_tables_specified_in_the_config()
    {
        $this->app['config']->set('db-snapshots.tables', ['users', 'posts']);

        Artisan::call('snapshot:create');

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/');
        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/');
        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
    }

    /** @test */
    public function it_can_create_a_snapshot_without_excluded_tables_specified_in_the_command_options()
    {
        if ($this->app['config']['database']['connections']['testing']['driver'] === 'sqlite') {
            $this->markTestSkipped('sqlite not supporting exclude functionality');
        }
        Artisan::call('snapshot:create', ['--exclude' => ['users', 'posts']]);

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE `users`/');
        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE `posts`/');
        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE `models`/');
    }

    /** @test */
    public function it_can_create_a_snapshot_without_excluded_tables_specified_in_the_command_options_as_a_string()
    {
        if ($this->app['config']['database']['connections']['testing']['driver'] === 'sqlite') {
            $this->markTestSkipped('sqlite not supporting exclude functionality');
        }
        Artisan::call('snapshot:create', ['--exclude' => 'users,posts']);

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE `users`/');
        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE `posts`/');
        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE `models`/');
    }

    /** @test */
    public function it_can_create_a_snapshot_without_excluded_tables_specified_in_the_config()
    {
        if ($this->app['config']['database']['connections']['testing']['driver'] === 'sqlite') {
            $this->markTestSkipped('sqlite not supporting exclude functionality');
        }
        $this->app['config']->set('db-snapshots.exclude', ['users', 'posts']);

        Artisan::call('snapshot:create');

        $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE `users`/');
        $this->assertFileOnDiskFailsRegex($fileName, '/CREATE TABLE `posts`/');
        $this->assertFileOnDiskPassesRegex($fileName, '/CREATE TABLE `models`/');
    }
}
