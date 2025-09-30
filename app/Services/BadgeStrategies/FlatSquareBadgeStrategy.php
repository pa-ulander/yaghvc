<?php

declare(strict_types=1);

namespace App\Services\BadgeStrategies;

use App\Contracts\BadgeRendererStrategyInterface;
use PUGX\Poser\Badge;
use PUGX\Poser\Render\SvgFlatSquareRender;

/**
 * Flat-square badge style strategy.
 *
 * Renders badges using the flat-square style from Poser library.
 * This style features sharp corners without rounded edges.
 */
class FlatSquareBadgeStrategy implements BadgeRendererStrategyInterface
{
    public function __construct(
        private readonly SvgFlatSquareRender $renderer
    ) {}

    /**
     * Render a flat-square-style badge.
     */
    public function render(string $label, string $message, string $color): string
    {
        $badge = new Badge(
            subject: $label,
            status: $message,
            color: $color,
            format: Badge::DEFAULT_FORMAT
        );

        return (string) $this->renderer->render($badge);
    }
}
