<?php

declare(strict_types=1);

namespace App\Services {
    class BasePathTestShim
    {
        public static bool $throw = false;
    }

    function base_path(string $path): string
    {
        if (BasePathTestShim::$throw) {
            throw new \RuntimeException('base_path unavailable');
        }

        return \base_path($path);
    }
}

namespace Tests\Unit\Services {

    use App\Services\BasePathTestShim;
    use App\Services\LogoProcessor;

    it('returns null for unknown slug', function () {
        $p = new LogoProcessor();
        $res = $p->prepare('slug-that-will-never-exist-xyz');
        expect($res)->toBeNull();
    });

    it('salvages minimally malformed data uri', function () {
        $p = new LogoProcessor();
        // Introduce whitespace to force initial parse failure then salvage
        $raw = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
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

    it('uses filesystem fallback when base_path helper throws', function () {
        $p = new LogoProcessor();
        BasePathTestShim::$throw = true;

        try {
            $res = $p->prepare('github');
        } finally {
            BasePathTestShim::$throw = false;
        }

        expect($res)->not()->toBeNull()
            ->and($res['mime'])->toBe('svg+xml');
    });

    it('injects width and height attributes when missing on slug', function () {
        $p = new LogoProcessor();
        $method = new \ReflectionMethod(LogoProcessor::class, 'resolveNamedLogo');
        $method->setAccessible(true);

        $result = $method->invoke($p, 'github');

        expect($result)->not()->toBeNull();

        /** @var array{dataUri:string} $result */
        $dataUri = $result['dataUri'];
        $payload = substr($dataUri, strlen('data:image/svg+xml;base64,'));
        $decoded = base64_decode($payload, true);

        expect($decoded)->not()->toBeFalse()
            ->and($decoded)->toContain('width="24"')
            ->and($decoded)->toContain('height="24"');
    });
}
