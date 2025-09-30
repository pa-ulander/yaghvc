<?php

declare(strict_types=1);

use App\ValueObjects\LogoRequest;

it('creates logo request with default values', function () {
    $request = new LogoRequest(raw: 'github');

    expect($request->raw)->toBe('github')
        ->and($request->logoSize)->toBeNull()
        ->and($request->targetHeight)->toBe(14)
        ->and($request->fixedSize)->toBe(14)
        ->and($request->maxBytes)->toBe(10000)
        ->and($request->maxDimension)->toBe(32)
        ->and($request->cacheTtl)->toBe(3600);
});

it('creates logo request with custom values', function () {
    $request = new LogoRequest(
        raw: 'data:image/png;base64,abc',
        logoSize: 'auto',
        targetHeight: 16,
        fixedSize: 20,
        maxBytes: 5000,
        maxDimension: 64,
        cacheTtl: 7200,
    );

    expect($request->raw)->toBe('data:image/png;base64,abc')
        ->and($request->logoSize)->toBe('auto')
        ->and($request->targetHeight)->toBe(16)
        ->and($request->fixedSize)->toBe(20)
        ->and($request->maxBytes)->toBe(5000)
        ->and($request->maxDimension)->toBe(64)
        ->and($request->cacheTtl)->toBe(7200);
});

it('generates consistent cache key', function () {
    $request1 = new LogoRequest(raw: 'github', logoSize: 'auto');
    $request2 = new LogoRequest(raw: 'github', logoSize: 'auto');

    expect($request1->getCacheKey())->toBe($request2->getCacheKey());
});

it('generates different cache keys for different inputs', function () {
    $request1 = new LogoRequest(raw: 'github', logoSize: 'auto');
    $request2 = new LogoRequest(raw: 'github', logoSize: '16');
    $request3 = new LogoRequest(raw: 'gitlab', logoSize: 'auto');

    expect($request1->getCacheKey())
        ->not()->toBe($request2->getCacheKey())
        ->not()->toBe($request3->getCacheKey());
});

it('cache key includes null logoSize correctly', function () {
    $request1 = new LogoRequest(raw: 'github', logoSize: null);
    $request2 = new LogoRequest(raw: 'github');

    expect($request1->getCacheKey())->toBe($request2->getCacheKey());
});

it('returns true when cache is enabled', function () {
    $request = new LogoRequest(raw: 'github', cacheTtl: 3600);

    expect($request->isCacheEnabled())->toBeTrue();
});

it('returns false when cache is disabled', function () {
    $request = new LogoRequest(raw: 'github', cacheTtl: 0);

    expect($request->isCacheEnabled())->toBeFalse();
});

it('is immutable readonly class', function () {
    $request = new LogoRequest(raw: 'github');

    expect((new ReflectionClass($request))->isFinal())->toBeTrue()
        ->and((new ReflectionClass($request))->isReadOnly())->toBeTrue();
});
