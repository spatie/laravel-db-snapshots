<?php

namespace Spatie\DbSnapshots\Test;

use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\Sqlite;
use Spatie\DbSnapshots\DbDumperFactory;
use Spatie\DbDumper\Databases\PostgreSql;
use Spatie\DbSnapshots\Exceptions\CannotCreateDbDumper;

class DbDumperFactoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

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
    }

    /** @test */
    public function it_can_create_instances_of_mysql_and_pgsql()
    {
        $this->assertInstanceOf(MySql::class, DbDumperFactory::createForConnection('mysql'));
        $this->assertInstanceOf(PostgreSql::class, DbDumperFactory::createForConnection('pgsql'));
    }

    /** @test */
    public function it_can_create_sqlite_instance()
    {
        $this->app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => 'database.sqlite',
            // host, username and password are not required for the sqlite driver
        ]);

        $this->assertInstanceOf(Sqlite::class, DbDumperFactory::createForConnection('sqlite'));
    }

    /** @test */
    public function it_will_use_the_read_db_when_one_is_defined()
    {
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

        $this->assertEquals('localhost-read', $dumper->getHost());
    }

    /** @test */
    public function it_will_throw_an_exception_when_creating_an_unknown_type_of_dumper()
    {
        $this->expectException(CannotCreateDbDumper::class);

        DbDumperFactory::createForConnection('unknown type');
    }

    /** @test */
    public function it_can_add_named_options_to_the_dump_command()
    {
        $dumpConfig = ['use_single_transaction'];

        $this->app['config']->set('database.connections.mysql.dump', $dumpConfig);

        $this->assertContains('--single-transaction', $this->getDumpCommand('mysql'));
    }

    /** @test */
    public function it_can_add_named_options_with_an_array_value_to_the_dump_command()
    {
        $dumpConfig = ['include_tables' => ['table1', 'table2']];

        $this->app['config']->set('database.connections.mysql.dump', $dumpConfig);

        $this->assertContains(implode(' ', $dumpConfig['include_tables']), $this->getDumpCommand('mysql'));
    }

    /** @test */
    public function it_can_add_arbritrary_options_to_the_dump_command()
    {
        $dumpConfig = ['add_extra_option' => '--extra-option=value'];

        $this->app['config']->set('database.connections.mysql.dump', $dumpConfig);

        $this->assertContains($dumpConfig['add_extra_option'], $this->getDumpCommand('mysql'));
    }

    /** @test */
    public function it_adds_the_inserts_option_to_the_pgsql_dump_command()
    {
        $this->assertContains('--inserts', $this->getDumpCommand('pgsql'));
    }

    protected function getDumpCommand(string $connectionName): string
    {
        $dumpFile = '';
        $credentialsFile = '';

        return DbDumperFactory::createForConnection($connectionName)->getDumpCommand($dumpFile, $credentialsFile);
    }
}
