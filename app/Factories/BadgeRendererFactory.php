<?php

declare(strict_types=1);

namespace App\Factories;

use App\Contracts\BadgeRendererStrategyInterface;
use App\Services\BadgeStrategies\FlatBadgeStrategy;
use App\Services\BadgeStrategies\FlatSquareBadgeStrategy;
use App\Services\BadgeStrategies\ForTheBadgeStrategy;
use App\Services\BadgeStrategies\PlasticBadgeStrategy;
use PUGX\Poser\Calculator\SvgTextSizeCalculator;
use PUGX\Poser\Render\SvgFlatRender;
use PUGX\Poser\Render\SvgFlatSquareRender;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;
use PUGX\Poser\Render\SvgPlasticRender;

/**
 * Factory for creating badge renderer strategies.
 *
 * Centralizes the creation of badge rendering strategies based on style name.
 * Supported styles: flat, flat-square, for-the-badge, plastic.
 * Unknown styles default to 'flat' for graceful degradation.
 */
class BadgeRendererFactory
{
    private SvgTextSizeCalculator $calculator;

    public function __construct()
    {
        // Share a single calculator instance across all strategies for memory efficiency
        $this->calculator = new SvgTextSizeCalculator();
    }

    /**
     * Create a badge renderer strategy for the given style.
     *
     * @param string $style The badge style (flat, flat-square, for-the-badge, plastic)
     * @return BadgeRendererStrategyInterface The strategy instance for rendering
     */
    public function create(string $style): BadgeRendererStrategyInterface
    {
        return match ($style) {
            'flat-square' => new FlatSquareBadgeStrategy(
                renderer: new SvgFlatSquareRender(textSizeCalculator: $this->calculator)
            ),
            'for-the-badge' => new ForTheBadgeStrategy(
                renderer: new SvgForTheBadgeRenderer(textSizeCalculator: $this->calculator)
            ),
            'plastic' => new PlasticBadgeStrategy(
                renderer: new SvgPlasticRender(textSizeCalculator: $this->calculator)
            ),
            default => new FlatBadgeStrategy(
                renderer: new SvgFlatRender(textSizeCalculator: $this->calculator)
            ),
        };
    }
}
