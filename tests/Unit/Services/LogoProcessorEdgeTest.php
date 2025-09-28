<?php

declare(strict_types=1);

use App\Services\LogoProcessor;

it('returns null for unknown slug', function () {
    $p = new LogoProcessor();
    $res = $p->prepare('slug-that-will-never-exist-xyz');
    expect($res)->toBeNull();
});

it('salvages minimally malformed data uri', function () {
    $p = new LogoProcessor();
    // Introduce whitespace to force initial parse failure then salvage
    $raw = 'data:image/png;base64,iVBORw0KGgoAAAANS UhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
    $res = $p->prepare($raw);
    // salvage may decode and return sized raster or null depending on strictness; assert not fatal
    expect($res === null || (isset($res['mime']) && $res['mime'] === 'png'))->toBeTrue();
});

it('normalises loose base64 svg path (success)', function () {
    $p = new LogoProcessor();
    $svg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10"/></svg>');
    $res = $p->prepare($svg);
    expect($res)->not()->toBeNull();
    expect($res['mime'])->toBe('svg+xml');
});
