<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use function PHPUnit\Framework\assertDoesNotMatchRegularExpression;
use function PHPUnit\Framework\assertMatchesRegularExpression;

uses(Spatie\DbSnapshots\Test\TestCase::class)->in('.');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('fileOnDiskToPassRegex', function (string $needle) {
    /** @var string */
    $fileName = $this->value;

    test()->disk->assertExists($fileName);

    $contents = test()->disk->get($fileName);

    assertMatchesRegularExpression($needle, $contents);

    return $this;
});

expect()->extend('fileOnDiskToFailRegex', function (string $needle) {
    /** @var string */
    $fileName = $this->value;

    test()->disk->assertExists($fileName);

    $contents = test()->disk->get($fileName);

    assertDoesNotMatchRegularExpression($needle, $contents);

    return $this;
});
