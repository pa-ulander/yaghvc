<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Immutable value object encapsulating parsed badge geometry.
 */
final class BadgeGeometryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $reason,
        public readonly ?float $totalWidth = null,
        public readonly ?float $height = null,
        public readonly ?float $labelWidth = null,
        public readonly ?float $statusWidth = null,
        public readonly ?float $statusX = null,
    ) {
    }

    public static function success(
        float $totalWidth,
        float $height,
        float $labelWidth,
        float $statusWidth,
        float $statusX,
    ): self {
        return new self(true, 'none', $totalWidth, $height, $labelWidth, $statusWidth, $statusX);
    }

    public static function failure(string $reason): self
    {
        return new self(false, $reason);
    }
}
