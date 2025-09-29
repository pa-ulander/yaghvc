<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LogoProcessor;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Test LogoProcessor cache path and isPreparedPayload validation branches.
 */
final class LogoProcessorCacheTest extends TestCase
{
    public function test_cache_hit_with_valid_payload(): void
    {
        $cacheKey = 'logo:' . sha1('github|auto');
        $validPayload = [
            'dataUri' => 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
            'width' => 14,
            'height' => 14,
            'mime' => 'svg+xml',
        ];
        Cache::put($cacheKey, $validPayload, 60);

        $processor = new LogoProcessor();
        $result = $processor->prepare(raw: 'github', logoSize: 'auto');

        $this->assertIsArray($result);
        $this->assertSame($validPayload['dataUri'], $result['dataUri']);
    }

    public function test_cache_miss_on_malformed_payload_missing_dataUri(): void
    {
        $cacheKey = 'logo:' . sha1('github|');
        $malformedPayload = [
            'width' => 14,
            'height' => 14,
            'mime' => 'svg+xml',
            // missing 'dataUri'
        ];
        Cache::put($cacheKey, $malformedPayload, 60);

        $processor = new LogoProcessor();
        $result = $processor->prepare(raw: 'github', logoSize: null);

        // Should bypass cache and resolve normally (if github slug exists)
        $this->assertIsArray($result);
    }

    public function test_cache_miss_on_malformed_payload_wrong_dataUri_type(): void
    {
        $cacheKey = 'logo:' . sha1('github|');
        $malformedPayload = [
            'dataUri' => 123, // wrong type, should be string
            'width' => 14,
            'height' => 14,
            'mime' => 'svg+xml',
        ];
        Cache::put($cacheKey, $malformedPayload, 60);

        $processor = new LogoProcessor();
        $result = $processor->prepare(raw: 'github', logoSize: null);

        $this->assertIsArray($result);
    }

    public function test_cache_miss_on_malformed_payload_wrong_width_type(): void
    {
        $cacheKey = 'logo:' . sha1('github|');
        $malformedPayload = [
            'dataUri' => 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
            'width' => '14', // wrong type, should be int
            'height' => 14,
            'mime' => 'svg+xml',
        ];
        Cache::put($cacheKey, $malformedPayload, 60);

        $processor = new LogoProcessor();
        $result = $processor->prepare(raw: 'github', logoSize: null);

        $this->assertIsArray($result);
    }

    public function test_cache_miss_on_malformed_payload_wrong_binary_type(): void
    {
        $cacheKey = 'logo:' . sha1('github|');
        $malformedPayload = [
            'dataUri' => 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
            'width' => 14,
            'height' => 14,
            'mime' => 'svg+xml',
            'binary' => 123, // wrong type if present
        ];
        Cache::put($cacheKey, $malformedPayload, 60);

        $processor = new LogoProcessor();
        $result = $processor->prepare(raw: 'github', logoSize: null);

        $this->assertIsArray($result);
    }

    public function test_configInt_with_string_numeric_value(): void
    {
        config(['badge.logo_cache_ttl' => '300']);
        $processor = new LogoProcessor();
        $ref = new \ReflectionClass($processor);
        $method = $ref->getMethod('configInt');
        $method->setAccessible(true);

        $result = $method->invoke($processor, 'badge.logo_cache_ttl', 100);
        $this->assertSame(300, $result);
    }

    public function test_configInt_with_float_value(): void
    {
        config(['badge.logo_cache_ttl' => 300.0]);
        $processor = new LogoProcessor();
        $ref = new \ReflectionClass($processor);
        $method = $ref->getMethod('configInt');
        $method->setAccessible(true);

        $result = $method->invoke($processor, 'badge.logo_cache_ttl', 100);
        $this->assertSame(300, $result);
    }

    public function test_configInt_with_invalid_value_returns_default(): void
    {
        config(['badge.logo_cache_ttl' => ['invalid']]);
        $processor = new LogoProcessor();
        $ref = new \ReflectionClass($processor);
        $method = $ref->getMethod('configInt');
        $method->setAccessible(true);

        $result = $method->invoke($processor, 'badge.logo_cache_ttl', 100);
        $this->assertSame(100, $result);
    }
}
