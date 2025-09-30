<?php

declare(strict_types=1);

namespace App\Services\BadgeStrategies;

use App\Contracts\BadgeRendererStrategyInterface;
use PUGX\Poser\Badge;
use PUGX\Poser\Render\SvgPlasticRender;

/**
 * Plastic badge style strategy.
 *
 * Renders badges using the plastic style from Poser library.
 * This style features a glossy, gradient appearance.
 */
class PlasticBadgeStrategy implements BadgeRendererStrategyInterface
{
    public function __construct(
        private readonly SvgPlasticRender $renderer
    ) {}

    /**
     * Render a plastic-style badge.
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
