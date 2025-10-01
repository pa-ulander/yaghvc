<?php

declare(strict_types=1);

use App\Services\LogoHandlers\DataUriLogoHandler;
use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

it('can handle data URIs', function () {
    $handler = new DataUriLogoHandler();
    // 1x1 red PNG
    $dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP4z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->dataUri)->toBe($dataUri)
        ->and($result->mime)->toBe('png')
        ->and($result->width)->toBe(14)
        ->and($result->height)->toBe(14);
});

it('cannot handle raw base64', function () {
    $handler = new DataUriLogoHandler();
    $request = new LogoRequest(
        raw: 'iVBORw0KGgoAAAANSUhEUg',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('cannot handle slugs', function () {
    $handler = new DataUriLogoHandler();
    $request = new LogoRequest(
        raw: 'github',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('parses SVG data URIs', function () {
    $handler = new DataUriLogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><circle cx="12" cy="12" r="10"/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->mime)->toBe('svg+xml')
        ->and($result->dataUri)->toBe($dataUri)
        ->and($result->binary)->toBe($svg);
});

it('extracts SVG dimensions from width/height attributes', function () {
    $handler = new DataUriLogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="32"><rect/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: 'auto',
        targetHeight: 20,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    // Auto sizing: aspect = 48/32 = 1.5, height = 20, width = 20 * 1.5 = 30
    expect($result->height)->toBe(20)
        ->and($result->width)->toBe(30);
});

it('extracts SVG dimensions from viewBox when attributes missing', function () {
    $handler = new DataUriLogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 50"><rect/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: 'auto',
        targetHeight: 25,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    // Auto sizing: aspect = 100/50 = 2.0, height = 25, width = 25 * 2.0 = 50
    expect($result->height)->toBe(25)
        ->and($result->width)->toBe(50);
});

it('applies numeric logoSize to SVG', function () {
    $handler = new DataUriLogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><circle/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: '32',
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->width)->toBe(32)
        ->and($result->height)->toBe(32);
});

it('clamps SVG numeric logoSize to maxDimension', function () {
    $handler = new DataUriLogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: '100',
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->width)->toBe(64)
        ->and($result->height)->toBe(64);
});

it('enforces minimum size of 8 for SVG', function () {
    $handler = new DataUriLogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: '3',
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->width)->toBe(8)
        ->and($result->height)->toBe(8);
});

it('parses raster image data URIs', function () {
    $handler = new DataUriLogoHandler();
    // 1x1 red PNG
    $dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP4z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->mime)->toBe('png')
        ->and($result->width)->toBe(14)
        ->and($result->height)->toBe(14);
});

it('applies numeric logoSize to raster images', function () {
    $handler = new DataUriLogoHandler();
    $dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP4z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: '24',
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->width)->toBe(24)
        ->and($result->height)->toBe(24);
});

it('rejects oversized data URIs', function () {
    $handler = new DataUriLogoHandler();
    // Create a large base64 string
    $largeData = str_repeat('A', 3000);
    $dataUri = 'data:image/png;base64,' . base64_encode($largeData);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 2048, // 2KB limit
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('rejects invalid base64 in data URI', function () {
    $handler = new DataUriLogoHandler();
    $dataUri = 'data:image/png;base64,not-valid-base64!!!';

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('rejects unsupported MIME types', function () {
    $handler = new DataUriLogoHandler();
    $dataUri = 'data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA=';

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('handles JPEG data URIs', function () {
    $handler = new DataUriLogoHandler();
    // Minimal JPEG header
    $jpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9";
    $dataUri = 'data:image/jpeg;base64,' . base64_encode($jpeg);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->mime)->toBe('jpeg');
});

it('handles GIF data URIs', function () {
    $handler = new DataUriLogoHandler();
    // Minimal GIF header
    $gif = "GIF89a\x01\x00\x01\x00\x00\x00\x00;";
    $dataUri = 'data:image/gif;base64,' . base64_encode($gif);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->mime)->toBe('gif');
});

it('salvages uncommon base64 variants', function () {
    $handler = new DataUriLogoHandler();
    // Data URI with spaces and different formatting
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>';
    $b64 = base64_encode($svg);
    // Add spaces that should be stripped
    $dataUri = 'data:image/svg+xml;base64, ' . $b64 . ' ';

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->mime)->toBe('svg+xml');
});

it('handles percent-encoded spaces in data URIs', function () {
    $handler = new DataUriLogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>';
    $b64 = base64_encode($svg);
    // Replace some characters with URL encoding
    $dataUri = 'data:image/svg+xml;base64,' . str_replace('+', '%20', $b64);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->mime)->toBe('svg+xml');
});

it('uses fixedSize when logoSize is null', function () {
    $handler = new DataUriLogoHandler();
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);

    $request = new LogoRequest(
        raw: $dataUri,
        logoSize: null,
        targetHeight: 20,
        fixedSize: 16,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null);
    expect($result->width)->toBe(16)
        ->and($result->height)->toBe(16);
});
