# Quickly dump and load databases

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-db-snapshots.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-db-snapshots)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![run-tests](https://github.com/spatie/laravel-db-snapshots/actions/workflows/run-tests.yml/badge.svg)](https://github.com/spatie/laravel-db-snapshots/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-db-snapshots.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-db-snapshots)

This package provides Artisan commands to quickly dump and load databases in a Laravel application.

```bash
# Create a dump
php artisan snapshot:create my-first-dump

# Make some changes to your db
# ...

# Create another dump
php artisan snapshot:create my-second-dump

# Load up the first dump
php artisan snapshot:load my-first-dump

# Load up the latest dump
php artisan snapshot:load --latest

# List all snapshots
php artisan snapshot:list

# Remove old snapshots. Keeping only the most recent
php artisan snapshot:cleanup --keep=2
```

This package supports MySQL, PostgreSQL and SQLite.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-db-snapshots.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-db-snapshots)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Support us

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

> For PHP 7.x and/or Laravel 6.x, use v1.x of this package.

You can install the package via Composer:

```bash
composer require spatie/laravel-db-snapshots
```

You should add a disk named `snapshots` to `config/filesystems.php` on which the snapshots will be saved. This would be a typical configuration:

```php
// ...
'disks' => [
    // ...
    'snapshots' => [
        'driver' => 'local',
        'root' => database_path('snapshots'),
    ],
// ...
```

Optionally, you may publish the configuration file with:

```bash
php artisan vendor:publish --provider="Spatie\DbSnapshots\DbSnapshotsServiceProvider"
```

This is the content of the published file:

```php
return [

    /*
     * The name of the disk on which the snapshots are stored.
     */
    'disk' => 'snapshots',

    /*
     * The connection to be used to create snapshots. Set this to null
     * to use the default configured in `config/databases.php`
     */
    'default_connection' => null,

    /*
     * The directory where temporary files will be stored.
     */
    'temporary_directory_path' => storage_path('app/laravel-db-snapshots/temp'),

    /*
     * Create dump files that are gzipped
     */
    'compress' => false,

    /*
     * Only these tables will be included in the snapshot. Set to `null` to include all tables.
     *
     * Default: `null`
     */
    'tables' => null,

    /*
     * All tables will be included in the snapshot expect this tables. Set to `null` to include all tables.
     *
     * Default: `null`
     */
    'exclude' => null,
];

```

## Usage

To create a snapshot (which is just a dump from the database) run:

```bash
php artisan snapshot:create my-first-dump
```

Giving your snapshot a name is optional. If you don't pass a name the current date time will be used:

```bash
# Creates a snapshot named something like `2017-03-17 14:31`
php artisan snapshot:create
```

If you pass a connection but do not declare a name for the snapshot, the connection will be prepended

```bash
# Creates a snapshot named something like `logging_2017-03-17 14:31`
php artisan snapshot:create --connection=logging
```

Maybe you only want to snapshot a couple of tables. You can do this by passing the `--table` multiple times or as a comma separated list:

```bash
# Both commands create a snapshot containing only the posts and users tables:
php artisan snapshot:create --table=posts,users
php artisan snapshot:create --table=posts --table=users
```

You may want to exclude some tables from snapshot. You can do this by passing the `--exclude` multiple times or as a comma separated list:
```bash
# create snapshot from all tables excluding the users and posts
php artisan snapshot:create --exclude=posts,users
php artisan snapshot:create --exclude=posts --exclude=users
```
> Note: if you pass `--table` and `--exclude` in the same time it will use `--table` to create the snapshot and it's ignore the `--exclude`

When creating snapshots, you can optionally create compressed snapshots.  To do this either pass the `--compress` option on the command line, or set the `db-snapshots.compress` configuration option to `true`:

```bash
# Creates a snapshot named my-compressed-dump.sql.gz
php artisan snapshot:create my-compressed-dump --compress
```

After you've made some changes to the database you can create another snapshot:

```bash
php artisan snapshot:create my-second-dump
```

To load a previous dump issue this command:

```bash
php artisan snapshot:load my-first-dump
```

To load a previous dump to another DB connection:

```bash
php artisan snapshot:load my-first-dump --connection=connectionName
```

By default, `snapshot:load` will drop all existing tables in the database. If you don't want this behaviour, you can pass the `--drop-tables=0` option:

```bash
php artisan snapshot:load my-first-dump --drop-tables=0
```

By default, `snapshot:load` will load the entire snapshot into memory which may cause problems when using large files. To avoid this, you can pass the `--stream` option to stream the snapshot to the database one statement at a time:

```bash
php artisan snapshot:load my-first-dump --stream
```

To list all the dumps run:

```bash
php artisan snapshot:list
```

A dump can be deleted with:

```bash
php artisan snapshot:delete my-first-dump
```

To remove all backups except the most recent 2

```bash
php artisan snapshot:cleanup --keep=2
```

If you need to pass extra options to the underlying db-dumper, add a `dump` key to the database **connection** with a key of `addExtraOption` and a value of the option. For example, to prevent the Postgres db dumper from setting the owner, you'd add:
```
'dump' => [
    'addExtraOption' => '--no-owner',
],
```
To the `pgsql` connection in `database.php`

## Events

There are several events fired which can be used to perform some logic of your own:

- `Spatie\DbSnapshots\Events\CreatingSnapshot`: will be fired before a snapshot is created
- `Spatie\DbSnapshots\Events\CreatedSnapshot`: will be fired after a snapshot has been created
- `Spatie\DbSnapshots\Events\LoadingSnapshot`: will be fired before a snapshot is loaded
- `Spatie\DbSnapshots\Events\LoadedSnapshot`: will be fired after a snapshot has been loaded
- `Spatie\DbSnapshots\Events\DeletingSnapshot`: will be fired before a snapshot is deleted
- `Spatie\DbSnapshots\Events\DeletedSnapshot`: will be fired after a snapshot has been deleted

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
