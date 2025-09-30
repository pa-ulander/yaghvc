<?php

declare(strict_types=1);

namespace App\Services\BadgeStrategies;

use App\Contracts\BadgeRendererStrategyInterface;
use PUGX\Poser\Badge;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;

/**
 * For-the-badge style strategy.
 *
 * Renders badges using the for-the-badge style from Poser library.
 * This style features larger, bolder text with uppercase transformation.
 */
class ForTheBadgeStrategy implements BadgeRendererStrategyInterface
{
    public function __construct(
        private readonly SvgForTheBadgeRenderer $renderer
    ) {
    }

    /**
     * Render a for-the-badge-style badge.
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
