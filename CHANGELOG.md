# Changelog

All notable changes to `laravel-db-snapshots` will be documented in this file

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
