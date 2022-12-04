<?php

use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbDumper\Databases\Sqlite;
use Spatie\DbSnapshots\DbDumperFactory;
use Spatie\DbSnapshots\Exceptions\CannotCreateDbDumper;

function getDumpCommand(string $connectionName): string
{
    $dumpFile = '';
    $credentialsFile = '';

    return DbDumperFactory::createForConnection($connectionName)->getDumpCommand($dumpFile, $credentialsFile);
}

beforeEach(function () {
    $this->app['config']->set('database.default', 'mysql');

    $dbConfig = [
        'driver' => 'mysql',
        'host' => 'localhost',
        'username' => 'root',
        'password' => 'myPassword',
        'database' => 'myDb',
        'dump' => ['add_extra_option' => '--extra-option=value'],
    ];

    $this->app['config']->set('database.connections.mysql', $dbConfig);
});

it('can create instances of MySQL and pgSQL')
    ->expect(fn () => DbDumperFactory::createForConnection('mysql'))
    ->toBeInstanceOf(MySql::class)
    ->and(fn () => DbDumperFactory::createForConnection('pgsql'))
    ->toBeInstanceOf(PostgreSql::class);

it('can create sqlite instance', function () {
    $this->app['config']->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => 'database.sqlite',
        // host, username and password are not required for the sqlite driver
    ]);

    expect(DbDumperFactory::createForConnection('sqlite'))
        ->toBeInstanceOf(Sqlite::class);
});

it('will use the read db when one is defined', function () {
    $dbConfig = [
        'driver' => 'mysql',
        'read' => [
            'host' => 'localhost-read',
        ],
        'write' => [
            'host' => 'localhost-write',
        ],
        'username' => 'root',
        'password' => 'myPassword',
        'database' => 'myDb',
        'dump' => ['add_extra_option' => '--extra-option=value'],
    ];

    $this->app['config']->set('database.connections.mysql', $dbConfig);

    $dumper = DbDumperFactory::createForConnection('mysql');

    expect($dumper->getHost())->toEqual('localhost-read');
});

it('will use connect via database when one is defined', function () {
    $dbConfig = [
        'driver' => 'pgsql',
        'connect_via_database' => 'connection_pool',
        'username' => 'root',
        'password' => 'myPassword',
        'database' => 'myDb',
        'dump' => ['add_extra_option' => '--extra-option=value'],
    ];

    $this->app['config']->set('database.connections.pgsql', $dbConfig);

    $dumper = DbDumperFactory::createForConnection('pgsql');

    expect($dumper->getDbName())->toEqual('connection_pool');
});

it('will throw an exception when creating an unknown type of dumper', function () {
    DbDumperFactory::createForConnection('unknown type');
})->throws(CannotCreateDbDumper::class);

it('will throw an exception when no disks are set up', function () {
    config()->set('filesystem.disks', null);

    DbDumperFactory::createForConnection('unknown type');
})->throws(CannotCreateDbDumper::class);

it('can add named options to the dump command', function () {
    $dumpConfig = ['use_single_transaction'];

    $this->app['config']->set('database.connections.mysql.dump', $dumpConfig);

    expect(getDumpCommand('mysql'))->toContain('--single-transaction');
});

it('can add named options with an array value to the dump command', function () {
    $dumpConfig = ['include_tables' => ['table1', 'table2']];

    $this->app['config']->set('database.connections.mysql.dump', $dumpConfig);

    expect(getDumpCommand('mysql'))->toContain(implode(' ', $dumpConfig['include_tables']));
});

it('can add arbitrary options to the dump command', function () {
    $dumpConfig = ['add_extra_option' => '--extra-option=value'];

    $this->app['config']->set('database.connections.mysql.dump', $dumpConfig);

    expect(getDumpCommand('mysql'))->toContain($dumpConfig['add_extra_option']);
});

it('adds the inserts option to the pgSQL dump command')
    ->expect(fn () => getDumpCommand('pgsql'))
    ->toContain('--inserts');

it('will use url when one is defined', function () {
    $dbConfig = [
        'driver' => 'mysql',
        'username' => 'root',
        'password' => 'myPassword',
        'database' => 'myDb',
        'host' => '127.0.0.1',
        'port' => '3306',
        'url' => 'mysql://otherUser:otherPass@otherHost:3307/otherDb',
    ];

    $this->app['config']->set('database.connections.mysql', $dbConfig);

    $dumper = DbDumperFactory::createForConnection('mysql');

    expect($dumper->getDbName())->toEqual('otherDb');
    expect($dumper->getHost())->toEqual('otherHost');
});
