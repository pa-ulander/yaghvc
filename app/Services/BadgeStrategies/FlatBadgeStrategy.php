<?php

declare(strict_types=1);

namespace App\Services\BadgeStrategies;

use App\Contracts\BadgeRendererStrategyInterface;
use PUGX\Poser\Badge;
use PUGX\Poser\Render\SvgFlatRender;

/**
 * Flat badge style strategy.
 *
 * Renders badges using the flat style from Poser library.
 * This is the default badge style with a clean, modern appearance.
 */
class FlatBadgeStrategy implements BadgeRendererStrategyInterface
{
    public function __construct(
        private readonly SvgFlatRender $renderer
    ) {
    }

    /**
     * Render a flat-style badge.
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
