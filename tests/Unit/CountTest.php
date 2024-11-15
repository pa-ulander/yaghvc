<?php

use App\Models\Count;

// Test constructor
it('creates a Count instance with a valid count', function () {
    $count = new Count(5);
    expect($count)->toBeInstanceOf(Count::class)
        ->and($count->toInt())->toBe(5);
});

it('throws an exception when count is greater than PHP_INT_MAX', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Max number of views reached');
    new Count(PHP_INT_MAX+1);
});

it('throws an exception when count is zero or negative', function ($invalidCount) {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Number of views can\'t be negative');
    new Count($invalidCount);
})->with([0, -1, -100]);

// Test ofString method
it('creates a Count instance from a valid string', function () {
    $count = Count::ofString('10');
    expect($count)->toBeInstanceOf(Count::class)
        ->and($count->toInt())->toBe(10);
});

it('throws an exception when creating from an invalid string', function ($invalidString) {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Base count can only be a number');
    Count::ofString($invalidString);
})->with(['abc', '1.5', '-5', '']);

// Test toInt method
it('returns the correct integer value', function () {
    $count = new Count(42);
    expect($count->toInt())->toBe(42);
});

// Test plus method
it('adds two Count instances correctly', function () {
    $count1 = new Count(5);
    $count2 = new Count(3);
    $result = $count1->plus($count2);
    expect($result)->toBeInstanceOf(Count::class)
        ->and($result->toInt())->toBe(8);
});

it('throws an exception when addition result exceeds PHP_INT_MAX', function () {
    $count1 = new Count(PHP_INT_MAX - 1);
    $count2 = new Count(2);
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Max number of views reached');
    $count1->plus($count2);
});

// Test factory
it('can create Count instances using the factory', function () {
    $count = Count::factory()->create();
    expect($count)->toBeInstanceOf(Count::class)
        ->and($count->toInt())->toBeGreaterThanOrEqual(1)
        ->and($count->toInt())->toBeLessThanOrEqual(1000);
});