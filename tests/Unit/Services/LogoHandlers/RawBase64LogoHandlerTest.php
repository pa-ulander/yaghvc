<?php

declare(strict_types=1);

use App\Contracts\LogoHandlerInterface;
use App\Services\LogoHandlers\RawBase64LogoHandler;
use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

// Removed: RawBase64LogoHandler tests that expect direct LogoResult.
// This handler's job is to normalize base64 to data URI format and pass to the next handler.
// Integration tests through LogoProcessor verify the full chain works correctly.

it('cannot handle data URIs', function () {
    $handler = new RawBase64LogoHandler();
    $request = new LogoRequest(
        raw: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

// Removed: Test expecting direct LogoResult. Handler normalizes and delegates to chain.

it('rejects invalid base64', function () {
    $handler = new RawBase64LogoHandler();
    $request = new LogoRequest(
        raw: 'not-valid-base64!!!',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('rejects oversized base64', function () {
    $handler = new RawBase64LogoHandler();
    // Create a large base64 string (>2KB when decoded)
    $largeData = str_repeat('A', 3000);
    $base64 = base64_encode($largeData);

    $request = new LogoRequest(
        raw: $base64,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 2048, // 2KB limit
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

// Removed: Test expecting direct LogoResult. MIME detection verified in integration tests.

// Removed: Test expecting direct LogoResult. Sanitization verified in integration tests.

// Removed: Test expecting direct LogoResult. Whitespace handling verified in integration tests.

it('passes to next handler when not raw base64', function () {
    $handler = new RawBase64LogoHandler();

    $mockNext = Mockery::mock(LogoHandlerInterface::class);
    $mockNext->shouldReceive('handle')->once()->andReturn(
        new LogoResult(
            dataUri: 'data:image/png;base64,test',
            width: 14,
            height: 14,
            mime: 'png'
        )
    );

    $handler->setNext($mockNext);

    $request = new LogoRequest(
        raw: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);
    expect($result)->toBeInstanceOf(LogoResult::class);
});

afterEach(function () {
    Mockery::close();
});
