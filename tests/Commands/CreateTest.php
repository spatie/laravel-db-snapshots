<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

it('can create a snapshot without a specific', function () {
    Artisan::call('snapshot:create');

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

    expect($fileName)
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/')
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/')
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/');
});

it('can create a snapshot with specific name')
    ->tap(fn () => Artisan::call('snapshot:create', ['name' => 'test']))
    ->expect('test.sql')
    ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');

it('can create a compressed snapshot from CLI param', function () {
    Artisan::call('snapshot:create', ['--compress' => true]);

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql.gz';

    $this->disk->assertExists($fileName);

    expect(
        gzdecode($this->disk->get($fileName))
    )->not->toBeEmpty();
});

it('can create a compressed snapshot from config', function () {
    $this->app['config']->set('db-snapshots.compress', true);

    Artisan::call('snapshot:create');

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql.gz';

    $this->disk->assertExists($fileName);

    expect(gzdecode($this->disk->get($fileName)))->not->toBeEmpty();
});

it('can create a snapshot with specific tables specified in the command options', function () {
    Artisan::call('snapshot:create', ['--table' => ['users', 'posts']]);

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

    expect($fileName)
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/')
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/')
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
});

it('can create a snapshot with specific tables specified in the command options as a string', function () {
    Artisan::call('snapshot:create', ['--table' => 'users,posts']);

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

    expect($fileName)
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/')
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/')
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
});

it('can create a snapshot with specific tables specified in the config', function () {
    $this->app['config']->set('db-snapshots.tables', ['users', 'posts']);

    Artisan::call('snapshot:create');

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

    expect($fileName)
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/')
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/')
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
});

it('can create a snapshot without excluded tables specified in the command options', function () {
    Artisan::call('snapshot:create', ['--exclude' => ['users', 'posts']]);

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

    expect($fileName)
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/')
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/')
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
});

it('can create a snapshot without excluded tables specified in the command options as a string', function () {
    Artisan::call('snapshot:create', ['--exclude' => 'users,posts']);

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

    expect($fileName)
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/')
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/')
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
});

it('can create a snapshot without excluded tables specified in the config', function () {
    $this->app['config']->set('db-snapshots.exclude', ['users', 'posts']);

    Artisan::call('snapshot:create');

    $fileName = Carbon::now()->format('Y-m-d_H-i-s') . '.sql';

    expect($fileName)
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "users"/')
        ->fileOnDiskToFailRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "posts"/')
        ->fileOnDiskToPassRegex('/CREATE TABLE(?: IF NOT EXISTS){0,1} "models"/');
});

it('passes extraOptions correctly to the command', function () {
    // Set up
    Storage::fake('snapshots');

    // Mock or set the extraOptions you want to test
    $extraOptions = ['--compress' => true, '--exclude-tables' => 'logs'];

    // Execute the artisan command with extra options
    Artisan::call('snapshot:create', [
        'name' => 'test_snapshot',
        '--disk' => 'snapshots',
        '--extraOptions' => json_encode($extraOptions),
    ]);

    // Assertions
    $output = Artisan::output();
    expect($output)->toContain('--compress')->toContain('true');
    expect($output)->toContain('--exclude-tables')->toContain('logs');

    // Verify the snapshot file was created
    $snapshotFileName = 'test_snapshot.sql';
    Storage::disk('snapshots')->assertExists($snapshotFileName);

    // Optionally, check the snapshot's content or metadata
    $snapshotContent = Storage::disk('snapshots')->get($snapshotFileName);
    expect($snapshotContent)->toContain('--compress')->toContain('--exclude-tables');
});
