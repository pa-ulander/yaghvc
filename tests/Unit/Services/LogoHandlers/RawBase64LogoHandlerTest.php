<?php

declare(strict_types=1);

use App\Contracts\LogoHandlerInterface;
use App\Services\LogoHandlers\RawBase64LogoHandler;
use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

it('can handle raw base64 strings', function () {
    $handler = new RawBase64LogoHandler();
    $request = new LogoRequest(
        raw: 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeInstanceOf(LogoResult::class);
});

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

it('normalizes valid base64 to data URI', function () {
    $handler = new RawBase64LogoHandler();
    // 1x1 red PNG
    $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP4z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';

    $request = new LogoRequest(
        raw: $base64,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan
    expect($result->dataUri)->toStartWith('data:image/')
        ->and($result->mime)->toBeIn(['png', 'jpeg', 'gif', 'svg+xml']);
});

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

it('detects SVG MIME type', function () {
    $handler = new RawBase64LogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><circle cx="12" cy="12" r="10"/></svg>';
    $base64 = base64_encode($svg);

    $request = new LogoRequest(
        raw: $base64,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan
    expect($result->mime)->toBe('svg+xml')
        ->and($result->dataUri)->toContain('data:image/svg+xml;base64,');
});

it('sanitizes SVG content', function () {
    $handler = new RawBase64LogoHandler();
    // SVG with script tag (should be sanitized)
    $maliciousSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script><circle cx="12" cy="12" r="10"/></svg>';
    $base64 = base64_encode($maliciousSvg);

    $request = new LogoRequest(
        raw: $base64,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan

    // Decode to check sanitization
    $decoded = base64_decode(explode(',', $result->dataUri)[1], true);
    expect($decoded)->not->toContain('<script>');
});

it('handles whitespace in base64', function () {
    $handler = new RawBase64LogoHandler();
    $base64 = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJ\nAAAADUlEQVR42mP4z8DwHwAF\nBQIAX8jx0gAAAABJRU5ErkJggg==";

    $request = new LogoRequest(
        raw: $base64,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan
    expect($result->dataUri)->toStartWith('data:image/');
});

it('passes to next handler when not raw base64', function () {
    $handler = new RawBase64LogoHandler();

    /** @var LogoHandlerInterface&\Mockery\MockInterface $mockNext */
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
