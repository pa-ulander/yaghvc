<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Immutable value object representing a logo processing request.
 *
 * Encapsulates all data needed by logo handlers in the Chain of Responsibility.
 */
final readonly class LogoRequest
{
    /**
     * @param string $raw The raw logo input (slug, data URI, or base64)
     * @param string|null $logoSize The requested logo size ('auto' or numeric string)
     * @param int $targetHeight Default target height for logos
     * @param int $fixedSize Fixed size when not using auto
     * @param int $maxBytes Maximum allowed bytes for decoded logo
     * @param int $maxDimension Maximum width/height for raster images
     * @param int $cacheTtl Cache TTL in seconds (0 = no cache)
     */
    public function __construct(
        public string $raw,
        public ?string $logoSize = null,
        public int $targetHeight = 14,
        public int $fixedSize = 14,
        public int $maxBytes = 10000,
        public int $maxDimension = 32,
        public int $cacheTtl = 3600,
    ) {
    }

    /**
     * Get cache key for this request.
     */
    public function getCacheKey(): string
    {
        return 'logo:' . sha1($this->raw . '|' . ($this->logoSize ?? ''));
    }

    /**
     * Check if caching is enabled.
     */
    public function isCacheEnabled(): bool
    {
        return $this->cacheTtl > 0;
    }
}
