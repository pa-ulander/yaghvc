<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Immutable value object encapsulating parsed badge geometry.
 *
 * @package App\Services
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
        return new self(success: true, reason: 'none', totalWidth: $totalWidth, height: $height, labelWidth: $labelWidth, statusWidth: $statusWidth, statusX: $statusX);
    }

    public static function failure(string $reason): self
    {
        return new self(success: false, reason: $reason);
    }
}
