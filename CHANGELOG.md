# Changelog

All notable changes to `laravel-db-snapshots` will be documented in this file

## 2.7.0 - 2024-12-11

### What's Changed

* Add extraOptions to dbDumper by @wattnpapa in https://github.com/spatie/laravel-db-snapshots/pull/150

### New Contributors

* @wattnpapa made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/150

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.6.2...2.7.0

## 2.6.2 - 2024-12-09

### What's Changed

* Prepend the connection onto the name by @michaeljhopkins in https://github.com/spatie/laravel-db-snapshots/pull/148

### New Contributors

* @michaeljhopkins made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/148

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.6.1...2.6.2

## 2.6.1 - 2024-04-02

### What's Changed

* Add MariaDB support by @francoism90 in https://github.com/spatie/laravel-db-snapshots/pull/147

### New Contributors

* @francoism90 made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/147

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.6.0...2.6.1

## 2.6.0 - 2024-03-08

### What's Changed

* Fix badges by @erikn69 in https://github.com/spatie/laravel-db-snapshots/pull/139
* Fix path in readme by @sebastianpopp in https://github.com/spatie/laravel-db-snapshots/pull/143
* Fix typo in README by @roerjo in https://github.com/spatie/laravel-db-snapshots/pull/146
* Laravel 11.x Compatibility by @laravel-shift in https://github.com/spatie/laravel-db-snapshots/pull/145

### New Contributors

* @erikn69 made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/139
* @sebastianpopp made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/143
* @roerjo made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/146

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.5.2...2.6.0

## 2.5.2 - 2023-03-27

### What's Changed

- fix uploading gz snapshot by @deonthomasgy in https://github.com/spatie/laravel-db-snapshots/pull/138

### New Contributors

- @deonthomasgy made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/138

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.5.1...2.5.2

## 2.5.1 - 2023-01-24

### What's Changed

- Refactor tests to Pest by @alexmanase in https://github.com/spatie/laravel-db-snapshots/pull/129
- Add PHP 8.2 Support by @patinthehat in https://github.com/spatie/laravel-db-snapshots/pull/135
- Laravel 10.x Compatibility by @laravel-shift in https://github.com/spatie/laravel-db-snapshots/pull/136

### New Contributors

- @alexmanase made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/129

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.5.0...2.5.1

## 2.5.0 - 2022-10-19

### What's Changed

- Typos in readme by @zoispag in https://github.com/spatie/laravel-db-snapshots/pull/126
- Support connect_via_database by @daniel-de-wit in https://github.com/spatie/laravel-db-snapshots/pull/127

### New Contributors

- @zoispag made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/126
- @daniel-de-wit made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/127

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.4.0...2.5.0

## 2.4.0 - 2022-09-02

### What's Changed

- add --exclude to the create command by @ariaieboy in https://github.com/spatie/laravel-db-snapshots/pull/125

### New Contributors

- @ariaieboy made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/125

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.3.1...2.4.0

## 2.3.1 - 2022-07-29

### What's Changed

- Fix undefined array key error in `SnapshotRepository::getAll()` by @eli-s-r in https://github.com/spatie/laravel-db-snapshots/pull/120

### New Contributors

- @eli-s-r made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/120

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.3.0...2.3.1

## 2.3.0 - 2022-04-20

## What's Changed

- Stream large snapshots by @jasonlfunk in https://github.com/spatie/laravel-db-snapshots/pull/118

## New Contributors

- @jasonlfunk made their first contribution in https://github.com/spatie/laravel-db-snapshots/pull/118

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.2.1...2.3.0

## 2.2.1 - 2022-01-14

- allow Laravel 9

## 2.2.0 - 2021-12-14

- add `--drop-tables` option to `snapshot:load`

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.1.0...2.2.0

## 2.1.0 - 2021-12-08

- Add `--table` option to specify tables to include in the snapshot

**Full Changelog**: https://github.com/spatie/laravel-db-snapshots/compare/2.0.0...2.1.0

## 2.0.0 - 2021-04-02

- require PHP 8+
- drop support for PHP 7
- drop support for Laravel 6
- use PHP 8 syntax
- implement `spatie/laravel-package-tools`

## 1.7.1 - 2020-12-02

- add support for PHP 8.0

## 1.7.0 - 2020-09-08

- add support for Laravel 8

## 1.6.2 - 2020-08-25

- fix cleaning up all snapshots (#102)

## 1.6.1 - 2020-06-01

- fix implode() exeception on PHP 7.4 (#88)
- drop support for anything below PHP 7.4

## 1.6.0 - 2020-05-11

- drop support for Laravel 5 and PHP 7.3

## 1.5.0 - 2020-03-03

- add support for Laravel 7

## 1.4.2 - 2020-02-27

- PHP7.2 syntax fix (#87)

## 1.4.1 - 2020-02-20

- allow read connections (#81)

## 1.4.0 - 2019-11-01

- add command to cleanup old snapshots (#79)

## 1.3.1 - 2019-10-29

- fix for obscure but repeatable issue relating to rmdir failing (#78)

## 1.3.0 - 2019-09-04

- add support for Laravel 6
- drop anything below Laravel 5.8
- drop anything below PHP 7.2

## 1.2.4 - 2019-07-01

- fix for load command in production

## 1.2.3 - 2019-06-28

- fix CannotCreateDisk exception when no disks are set up.

## 1.2.2 - 2019-06-27

- fix compression option from config not being respected

## 1.2.1 - 2019-05-24

- use `--inserts` option for pgsql connections by default

## 1.2.0 - 2019-04-06

- support creating and loading gzip snapshots

## 1.1.5 - 2018-05-03

- fix bug when trying to close a file that is already closed

## 1.1.4 - 2018-04-18

- improve output of the delete command when there are no snapshots yet

## 1.1.3 - 2017-12-05

- prevent tables being dropped on the non-default connection

## 1.1.2 - 2017-09-26

- fix for windows users

## 1.1.1 - 2017-08-04

- fix `connection` option to load snapshot command

## 1.1.0 - 2017-06-13

- add `connection` option to load snapshot command

## 1.0.1 - 2017-04-07

- move testbench to dev dependencies

## 1.0.0 - 2017-03-24

- initial release
