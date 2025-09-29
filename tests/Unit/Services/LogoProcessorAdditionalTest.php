<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LogoProcessor;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class LogoProcessorAdditionalTest extends TestCase
{
    private function invokePrivate(object $obj, string $method, array $args = [])
    {
        $ref = new \ReflectionClass($obj);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invokeArgs($obj, $args);
    }

    public function testPrepareReturnsNullOnEmpty(): void
    {
        $p = new LogoProcessor();
        $this->assertNull($p->prepare(null));
        $this->assertNull($p->prepare(''));
    }

    public function testOversizeRasterRejected(): void
    {
        Config::set('badge.logo_max_bytes', 10);
        // 1x1 png >10 bytes binary
        $dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB';
        $p = new LogoProcessor();
        $this->assertNull($p->prepare($dataUri));
    }

    public function testNumericSizeClamp(): void
    {
        Config::set('badge.logo_max_dimension', 16);
        $svg = 'data:image/svg+xml;base64,' . base64_encode('<svg width="100" height="50" xmlns="http://www.w3.org/2000/svg"></svg>');
        $p = new LogoProcessor();
        $res = $p->prepare($svg, '999'); // will clamp to 16
        $this->assertNotNull($res);
        $this->assertSame(16, $res['width']);
        $this->assertSame(16, $res['height']);
    }

    public function testAutoSizingSvg(): void
    {
        $svg = 'data:image/svg+xml;base64,' . base64_encode('<svg width="40" height="20" xmlns="http://www.w3.org/2000/svg"></svg>');
        $p = new LogoProcessor();
        $res = $p->prepare($svg, 'auto');
        $this->assertNotNull($res);
        $this->assertTrue($res['width'] > $res['height']);
    }

    public function testCachePathHit(): void
    {
        Config::set('badge.logo_cache_ttl', 60);
        $p = new LogoProcessor();
        $svgData = 'data:image/svg+xml;base64,' . base64_encode('<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg"></svg>');
        $first = $p->prepare($svgData, null);
        $this->assertNotNull($first);
        // Remove binary to mimic cached payload shape
        $cached = $first;
        unset($cached['binary']);
        $key = 'logo:' . sha1($svgData . '|');
        Cache::put($key, $cached, 60);
        // Second call should return cached (no binary key)
        $second = $p->prepare($svgData, null);
        $this->assertNotNull($second);
        $this->assertArrayNotHasKey('binary', $second); // retrieved from cache
    }

    public function testParseDataUriSalvage(): void
    {
        $p = new LogoProcessor();
        // Break regex by adding unsupported mime token but salvage logic should attempt decode
        $raw = 'data:image/xxpng;base64,' . base64_encode('PNG');
        $res = $this->invokePrivate($p, 'parseDataUri', [$raw]);
        $this->assertNull($res); // direct parse fails
        // Now call prepare to execute salvage path; salvage returns sized structure with custom mime token
        $prepared = $p->prepare($raw);
        $this->assertIsArray($prepared);
        $this->assertSame('xxpng', $prepared['mime']);
    }

    public function testDecodeDataUrlFromQueryParamEarlyReturn(): void
    {
        $p = new LogoProcessor();
        $out = $this->invokePrivate($p, 'decodeDataUrlFromQueryParam', ['just-a-string']);
        $this->assertNull($out);
    }
}
