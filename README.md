# An artisan command to dump, load and swap databases

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-db-loader.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-db-loader)
[![Build Status](https://img.shields.io/travis/spatie/laravel-db-loader/master.svg?style=flat-square)](https://travis-ci.org/spatie/laravel-db-loader)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/laravel-db-loader.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/laravel-db-loader)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-db-loader.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-db-loader)

This is where your description should go. Try and limit it to a paragraph or two, and maybe throw in a mention of what PSRs you support to avoid any confusion with users and contributors.

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

We publish all received postcards [on our company website](https://spatie.be/en/opensource/postcards).

## Installation

You can install the package via composer:

``` bash
composer require spatie/laravel-db-loader
```

## Usage

``` php
$db->loader = new Spatie\DbLoader();
echo $db->loader->echoPhrase('Hello, Spatie!');
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## About Spatie

Spatie is a webdesign agency based in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
