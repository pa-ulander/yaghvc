<?php

declare(strict_types=1);

namespace App\Services\LogoHandlers;

use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;
use Illuminate\Support\Facades\Cache;

/**
 * Cache handler for logo processing.
 *
 * First handler in the chain - checks cache for previously processed logos.
 * On cache miss, passes to next handler and caches the result.
 */
class CacheLogoHandler extends AbstractLogoHandler
{
    /**
     * Cache is always checked if enabled.
     */
    protected function canHandle(LogoRequest $request): bool
    {
        return $request->isCacheEnabled();
    }

    /**
     * Check cache and return cached result if available.
     * On miss, delegate to next handler and cache the result.
     */
    protected function process(LogoRequest $request): ?LogoResult
    {
        $cacheKey = $request->getCacheKey();

        // Try to get from cache
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            /** @var array<string,mixed> $cached */
            if ($this->isValidCachePayload($cached)) {
                /** @var array{dataUri:string,width:int,height:int,mime:string,binary?:string} $cached */
                return LogoResult::fromArray($cached);
            }
        }

        // Cache miss - delegate to next handler
        $result = $this->handleNext($request);

        // Cache the result if successful (excluding binary for size)
        if ($result !== null) {
            $cachePayload = $result->toArray();
            unset($cachePayload['binary']);
            Cache::put($cacheKey, $cachePayload, $request->cacheTtl);
        }

        return $result;
    }

    /**
     * Validate that cached payload has required structure.
     *
     * @param array<string,mixed> $payload
     */
    private function isValidCachePayload(array $payload): bool
    {
        return isset($payload['dataUri'], $payload['width'], $payload['height'], $payload['mime'])
            && is_string($payload['dataUri'])
            && is_int($payload['width'])
            && is_int($payload['height'])
            && is_string($payload['mime']);
    }
}
