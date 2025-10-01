<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Strategy interface for badge rendering.
 *
 * Defines the contract for different badge style implementations.
 * Each strategy encapsulates a specific badge rendering approach
 * (e.g., flat, flat-square, for-the-badge, plastic).
 */
interface BadgeRendererStrategyInterface
{
    /**
     * Render a badge with the given parameters.
     *
     * @param string $label The left-side label text
     * @param string $message The right-side message text (typically the count)
     * @param string $color The color for the message side (hex code or color name)
     * @return string The rendered SVG badge as a string
     */
    public function render(string $label, string $message, string $color): string;
}
