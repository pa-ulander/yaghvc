<?php

declare(strict_types=1);

use App\Contracts\LogoHandlerInterface;
use App\Services\LogoHandlers\CacheLogoHandler;
use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;
use Illuminate\Support\Facades\Cache;

it('returns cached result when available', function () {
    $request = new LogoRequest(
        raw: 'github',
        logoSize: 'auto',
        cacheTtl: 3600
    );

    $cachedData = [
        'dataUri' => 'data:image/svg+xml;base64,cached',
        'width' => 16,
        'height' => 16,
        'mime' => 'svg+xml',
    ];

    Cache::put($request->getCacheKey(), $cachedData, 3600);

    $handler = new CacheLogoHandler();
    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class)
        ->and($result->dataUri)->toBe('data:image/svg+xml;base64,cached')
        ->and($result->width)->toBe(16)
        ->and($result->height)->toBe(16)
        ->and($result->mime)->toBe('svg+xml');
});

it('bypasses cache when disabled', function () {
    $request = new LogoRequest(
        raw: 'github',
        cacheTtl: 0
    );

    $handler = new CacheLogoHandler();
    $result = $handler->handle($request);

    // Should return null since there's no next handler and cache is disabled
    expect($result)->toBeNull();
});

it('passes to next handler on cache miss', function () {
    $request = new LogoRequest(
        raw: 'newlogo',
        cacheTtl: 3600
    );

    Cache::forget($request->getCacheKey());

    $expectedResult = new LogoResult(
        dataUri: 'data:image/svg+xml;base64,new',
        width: 24,
        height: 24,
        mime: 'svg+xml'
    );

    $nextHandler = Mockery::mock(LogoHandlerInterface::class);
    $nextHandler->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(LogoRequest::class))
        ->andReturn($expectedResult);

    $handler = new CacheLogoHandler();
    $handler->setNext($nextHandler);

    $result = $handler->handle($request);

    expect($result)->toBe($expectedResult);
})->after(fn() => Mockery::close());

it('caches result from next handler', function () {
    $request = new LogoRequest(
        raw: 'gitlab',
        logoSize: '16',
        cacheTtl: 1800
    );

    Cache::forget($request->getCacheKey());

    $nextResult = new LogoResult(
        dataUri: 'data:image/svg+xml;base64,result',
        width: 16,
        height: 16,
        mime: 'svg+xml',
        binary: 'should-not-be-cached'
    );

    $nextHandler = Mockery::mock(LogoHandlerInterface::class);
    $nextHandler->shouldReceive('handle')
        ->once()
        ->andReturn($nextResult);

    $handler = new CacheLogoHandler();
    $handler->setNext($nextHandler);

    $result = $handler->handle($request);

    // Verify result is returned
    expect($result)->toBe($nextResult);

    // Verify it was cached (without binary)
    $cached = Cache::get($request->getCacheKey());
    expect($cached)->toBeArray()
        ->and($cached['dataUri'])->toBe('data:image/svg+xml;base64,result')
        ->and($cached)->not()->toHaveKey('binary');
})->after(fn() => Mockery::close());

it('does not cache null result', function () {
    $request = new LogoRequest(
        raw: 'invalid-unique-key',
        cacheTtl: 3600
    );

    Cache::forget($request->getCacheKey());

    $nextHandler = Mockery::mock(LogoHandlerInterface::class);
    $nextHandler->shouldReceive('handle')
        ->once()
        ->andReturn(null);

    $handler = new CacheLogoHandler();
    $handler->setNext($nextHandler);

    $result = $handler->handle($request);

    expect($result)->toBeNull()
        ->and(Cache::has($request->getCacheKey()))->toBeFalse();
})->after(fn() => Mockery::close());

it('ignores invalid cache payload', function () {
    $request = new LogoRequest(
        raw: 'test-payload',
        cacheTtl: 3600
    );

    // Put invalid cache data
    Cache::put($request->getCacheKey(), ['invalid' => 'data'], 3600);

    $expectedResult = new LogoResult(
        dataUri: 'data:image/png;base64,fixed',
        width: 16,
        height: 16,
        mime: 'png'
    );

    $nextHandler = Mockery::mock(LogoHandlerInterface::class);
    $nextHandler->shouldReceive('handle')
        ->once()
        ->andReturn($expectedResult);

    $handler = new CacheLogoHandler();
    $handler->setNext($nextHandler);

    $result = $handler->handle($request);

    expect($result)->toBe($expectedResult);
})->after(fn() => Mockery::close());

it('validates cache payload has required fields', function () {
    $request = new LogoRequest(raw: 'test-fields', cacheTtl: 3600);

    // Test missing fields
    Cache::put($request->getCacheKey(), ['dataUri' => 'test'], 3600);

    $handler = new CacheLogoHandler();
    $result = $handler->handle($request);

    // Falls through to null since no next handler
    expect($result)->toBeNull();
});
