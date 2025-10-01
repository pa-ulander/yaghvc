<?php

declare(strict_types=1);

use App\ValueObjects\LogoResult;

it('creates logo result with all parameters', function () {
    $result = new LogoResult(
        dataUri: 'data:image/png;base64,abc',
        width: 16,
        height: 16,
        mime: 'png',
        binary: 'binary-data'
    );

    expect($result->dataUri)->toBe('data:image/png;base64,abc')
        ->and($result->width)->toBe(16)
        ->and($result->height)->toBe(16)
        ->and($result->mime)->toBe('png')
        ->and($result->binary)->toBe('binary-data');
});

it('creates logo result without binary', function () {
    $result = new LogoResult(
        dataUri: 'data:image/svg+xml;base64,xyz',
        width: 24,
        height: 24,
        mime: 'svg+xml'
    );

    expect($result->dataUri)->toBe('data:image/svg+xml;base64,xyz')
        ->and($result->width)->toBe(24)
        ->and($result->height)->toBe(24)
        ->and($result->mime)->toBe('svg+xml')
        ->and($result->binary)->toBeNull();
});

it('converts to array with binary', function () {
    $result = new LogoResult(
        dataUri: 'data:image/png;base64,test',
        width: 32,
        height: 32,
        mime: 'png',
        binary: 'test-binary'
    );

    $array = $result->toArray();

    expect($array)->toBe([
        'dataUri' => 'data:image/png;base64,test',
        'width' => 32,
        'height' => 32,
        'mime' => 'png',
        'binary' => 'test-binary',
    ]);
});

it('converts to array without binary', function () {
    $result = new LogoResult(
        dataUri: 'data:image/svg+xml;base64,svg',
        width: 20,
        height: 20,
        mime: 'svg+xml'
    );

    $array = $result->toArray();

    expect($array)->toBe([
        'dataUri' => 'data:image/svg+xml;base64,svg',
        'width' => 20,
        'height' => 20,
        'mime' => 'svg+xml',
    ])
        ->and($array)->not()->toHaveKey('binary');
});

it('creates from array with binary', function () {
    $array = [
        'dataUri' => 'data:image/png;base64,abc',
        'width' => 16,
        'height' => 16,
        'mime' => 'png',
        'binary' => 'binary-content',
    ];

    $result = LogoResult::fromArray($array);

    expect($result->dataUri)->toBe('data:image/png;base64,abc')
        ->and($result->width)->toBe(16)
        ->and($result->height)->toBe(16)
        ->and($result->mime)->toBe('png')
        ->and($result->binary)->toBe('binary-content');
});

it('creates from array without binary', function () {
    $array = [
        'dataUri' => 'data:image/svg+xml;base64,xyz',
        'width' => 24,
        'height' => 24,
        'mime' => 'svg+xml',
    ];

    $result = LogoResult::fromArray($array);

    expect($result->dataUri)->toBe('data:image/svg+xml;base64,xyz')
        ->and($result->width)->toBe(24)
        ->and($result->height)->toBe(24)
        ->and($result->mime)->toBe('svg+xml')
        ->and($result->binary)->toBeNull();
});

it('roundtrips through array conversion', function () {
    $original = new LogoResult(
        dataUri: 'data:image/png;base64,test',
        width: 18,
        height: 18,
        mime: 'png',
        binary: 'test-data'
    );

    $array = $original->toArray();
    $restored = LogoResult::fromArray($array);

    expect($restored->dataUri)->toBe($original->dataUri)
        ->and($restored->width)->toBe($original->width)
        ->and($restored->height)->toBe($original->height)
        ->and($restored->mime)->toBe($original->mime)
        ->and($restored->binary)->toBe($original->binary);
});

it('is immutable readonly class', function () {
    $result = new LogoResult(
        dataUri: 'data:image/png;base64,test',
        width: 16,
        height: 16,
        mime: 'png'
    );

    expect((new ReflectionClass($result))->isFinal())->toBeTrue()
        ->and((new ReflectionClass($result))->isReadOnly())->toBeTrue();
});
