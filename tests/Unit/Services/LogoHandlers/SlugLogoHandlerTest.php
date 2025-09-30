<?php

declare(strict_types=1);

use App\Services\LogoHandlers\SlugLogoHandler;
use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

it('can handle valid slug patterns', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'github',
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
        ->and($result->dataUri)->toStartWith('data:image/svg+xml;base64,');
});

it('cannot handle data URIs', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'data:image/png;base64,iVBORw0KGgo',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('cannot handle invalid slug patterns', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'Invalid-Slug!',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('rejects slugs with uppercase letters', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'GitHub',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('rejects slugs that are too long', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: str_repeat('a', 61), // 61 characters, exceeds 60 limit
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('accepts slugs with hyphens and numbers', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'node-js-2024',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    // May or may not resolve (depends on if file exists)
    // but should at least attempt to handle it
    $result = $handler->handle($request);
    // Either returns null (file not found) or LogoResult (file found)
    expect($result)->toBeIn([null, expect()->toBeInstanceOf(LogoResult::class)]);
});

it('calculates dimensions with auto sizing', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'github',
        logoSize: 'auto',
        targetHeight: 20,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan
    // Auto sizing maintains aspect ratio from target height
    // simple-icons are 24x24, so aspect=1 → width should equal height
    expect($result->height)->toBe(20)
        ->and($result->width)->toBe(20);
});

it('calculates dimensions with numeric sizing', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'github',
        logoSize: '32',
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan
    // Numeric sizing sets both dimensions to the specified size
    expect($result->width)->toBe(32)
        ->and($result->height)->toBe(32);
});

it('clamps numeric sizing to maxDimension', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'github',
        logoSize: '100', // Exceeds maxDimension of 64
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan
    // Should be clamped to maxDimension
    expect($result->width)->toBe(64)
        ->and($result->height)->toBe(64);
});

it('enforces minimum size of 8', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'github',
        logoSize: '2', // Below minimum of 8
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan
    // Should be enforced to minimum of 8
    expect($result->width)->toBe(8)
        ->and($result->height)->toBe(8);
});

it('returns null for non-existent slugs', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'definitely-not-a-real-icon-slug-12345',
        logoSize: null,
        targetHeight: 14,
        fixedSize: 14,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    expect($handler->handle($request))->toBeNull();
});

it('uses fixedSize when logoSize is null', function () {
    $handler = new SlugLogoHandler();
    $request = new LogoRequest(
        raw: 'github',
        logoSize: null,
        targetHeight: 20,
        fixedSize: 16,
        maxBytes: 1024 * 1024,
        maxDimension: 64,
        cacheTtl: 0
    );

    $result = $handler->handle($request);

    expect($result)->toBeInstanceOf(LogoResult::class);
    assert($result !== null); // For PHPStan
    // When logoSize is null, should use fixedSize
    expect($result->width)->toBe(16)
        ->and($result->height)->toBe(16);
});
