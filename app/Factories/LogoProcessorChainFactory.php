<?php

declare(strict_types=1);

namespace App\Factories;

use App\Contracts\LogoHandlerInterface;
use App\Services\LogoHandlers\CacheLogoHandler;
use App\Services\LogoHandlers\DataUriLogoHandler;
use App\Services\LogoHandlers\RawBase64LogoHandler;
use App\Services\LogoHandlers\SlugLogoHandler;
use App\Services\LogoHandlers\UrlDecodedLogoHandler;
use App\ValueObjects\LogoRequest;

/**
 * Factory for assembling the logo processing chain of responsibility.
 *
 * Builds a handler chain in the correct order:
 * Cache → RawBase64 → UrlDecoded → Slug → DataUri
 *
 * Each handler transforms or validates the logo input, passing it
 * to the next handler until one successfully processes it or the
 * chain terminates.
 */
class LogoProcessorChainFactory
{
    /**
     * Build and return the logo processing handler chain.
     *
     * The chain processes logos in this order:
     * 1. Cache: Check for previously processed result
     * 2. RawBase64: Normalize raw base64 to data URI
     * 3. UrlDecoded: Handle percent-encoded data URIs
     * 4. Slug: Resolve simple-icons slugs
     * 5. DataUri: Parse and validate data URIs
     *
     * @param LogoRequest $request Configuration for the chain
     * @return LogoHandlerInterface First handler in the chain
     */
    public static function create(LogoRequest $request): LogoHandlerInterface
    {
        // Build chain from last to first
        $dataUriHandler = new DataUriLogoHandler();
        $slugHandler = new SlugLogoHandler();
        $urlDecodedHandler = new UrlDecodedLogoHandler();
        $rawBase64Handler = new RawBase64LogoHandler();
        $cacheHandler = new CacheLogoHandler();

        // Link handlers together
        $cacheHandler
            ->setNext($rawBase64Handler)
            ->setNext($urlDecodedHandler)
            ->setNext($slugHandler)
            ->setNext($dataUriHandler);

        return $cacheHandler;
    }

    /**
     * Create a LogoRequest from individual parameters.
     *
     * Helper method to construct a LogoRequest value object with
     * sensible defaults from configuration.
     *
     * @param string $raw Raw logo input (base64, slug, or data URI)
     * @param string|null $logoSize Size specification ('auto' or numeric)
     * @param int $targetHeight Target height for auto-sizing
     * @param int $fixedSize Fixed size when logoSize is null
     * @param int|null $maxBytes Maximum binary size (null = from config)
     * @param int|null $maxDimension Maximum dimension (null = from config)
     * @param int|null $cacheTtl Cache TTL in seconds (null = from config)
     * @return LogoRequest
     */
    public static function createRequest(
        string $raw,
        ?string $logoSize = null,
        int $targetHeight = 14,
        int $fixedSize = 14,
        ?int $maxBytes = null,
        ?int $maxDimension = null,
        ?int $cacheTtl = null
    ): LogoRequest {
        return new LogoRequest(
            raw: $raw,
            logoSize: $logoSize,
            targetHeight: $targetHeight,
            fixedSize: $fixedSize,
            maxBytes: $maxBytes ?? self::getConfigInt('badge.logo.max_bytes', 1024 * 1024),
            maxDimension: $maxDimension ?? self::getConfigInt('badge.logo.max_dimension', 64),
            cacheTtl: $cacheTtl ?? self::getConfigInt('badge.logo.cache_ttl', 3600)
        );
    }

    /**
     * Safely get an integer config value.
     */
    private static function getConfigInt(string $key, int $default): int
    {
        if (!function_exists('config')) {
            return $default;
        }

        $value = config($key, $default);

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }
}
