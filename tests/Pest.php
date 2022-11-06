<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use Illuminate\Database\Schema\SchemaState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

use function PHPUnit\Framework\assertDoesNotMatchRegularExpression;
use function PHPUnit\Framework\assertFalse;
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

// Functions

function seeInConsoleOutput(string|array $searchStrings): void
{
    if (!is_array($searchStrings)) {
        $searchStrings = [$searchStrings];
    }

    $output = Artisan::output();

    foreach ($searchStrings as $searchString) {
        expect($output)->toContain((string) $searchString);
    }
}

function assertTableNotExists(string $table): void
{
    assertFalse(
        Schema::hasTable($table),
        "Table {$table} should not exist"
    );
}
