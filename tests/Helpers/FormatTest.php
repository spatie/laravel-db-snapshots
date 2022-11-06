<?php

use Spatie\DbSnapshots\Helpers\Format;

it('can determine a human readable file size')
    ->expect(fn () => Format::humanReadableSize(10))->toEqual('10 B')
    ->and(fn () => Format::humanReadableSize(100))->toEqual('100 B')
    ->and(fn () => Format::humanReadableSize(1000))->toEqual('1000 B')
    ->and(fn () => Format::humanReadableSize(10000))->toEqual('9.77 KB')
    ->and(fn () => Format::humanReadableSize(1000000))->toEqual('976.56 KB')
    ->and(fn () => Format::humanReadableSize(10000000))->toEqual('9.54 MB')
    ->and(fn () => Format::humanReadableSize(10000000000))->toEqual('9.31 GB');
