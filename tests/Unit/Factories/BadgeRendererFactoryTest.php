<?php

declare(strict_types=1);

use App\Contracts\BadgeRendererStrategyInterface;
use App\Factories\BadgeRendererFactory;
use App\Services\BadgeStrategies\FlatBadgeStrategy;
use App\Services\BadgeStrategies\FlatSquareBadgeStrategy;
use App\Services\BadgeStrategies\ForTheBadgeStrategy;
use App\Services\BadgeStrategies\PlasticBadgeStrategy;

it('creates flat badge strategy', function () {
    $factory = new BadgeRendererFactory();
    $strategy = $factory->create('flat');

    expect($strategy)
        ->toBeInstanceOf(BadgeRendererStrategyInterface::class)
        ->toBeInstanceOf(FlatBadgeStrategy::class);
});

it('creates flat-square badge strategy', function () {
    $factory = new BadgeRendererFactory();
    $strategy = $factory->create('flat-square');

    expect($strategy)
        ->toBeInstanceOf(BadgeRendererStrategyInterface::class)
        ->toBeInstanceOf(FlatSquareBadgeStrategy::class);
});

it('creates for-the-badge strategy', function () {
    $factory = new BadgeRendererFactory();
    $strategy = $factory->create('for-the-badge');

    expect($strategy)
        ->toBeInstanceOf(BadgeRendererStrategyInterface::class)
        ->toBeInstanceOf(ForTheBadgeStrategy::class);
});

it('creates plastic badge strategy', function () {
    $factory = new BadgeRendererFactory();
    $strategy = $factory->create('plastic');

    expect($strategy)
        ->toBeInstanceOf(BadgeRendererStrategyInterface::class)
        ->toBeInstanceOf(PlasticBadgeStrategy::class);
});

it('returns default flat strategy for unknown style', function () {
    $factory = new BadgeRendererFactory();
    $strategy = $factory->create('unknown-style');

    expect($strategy)
        ->toBeInstanceOf(BadgeRendererStrategyInterface::class)
        ->toBeInstanceOf(FlatBadgeStrategy::class);
});

it('all strategies implement the interface', function () {
    $factory = new BadgeRendererFactory();
    $styles = ['flat', 'flat-square', 'for-the-badge', 'plastic', 'unknown'];

    foreach ($styles as $style) {
        $strategy = $factory->create($style);
        expect($strategy)->toBeInstanceOf(BadgeRendererStrategyInterface::class);
    }
});

it('can render badges with created strategies', function () {
    $factory = new BadgeRendererFactory();

    $flatStrategy = $factory->create('flat');
    $result = $flatStrategy->render('test', 'badge', 'blue');

    expect(trim($result))
        ->toBeString()
        ->toStartWith('<svg')
        ->toEndWith('</svg>')
        ->toContain('test')
        ->toContain('badge');
});

it('creates strategies efficiently', function () {
    $factory = new BadgeRendererFactory();

    // Create multiple strategies - should be fast due to shared calculator
    $start = microtime(true);
    for ($i = 0; $i < 10; $i++) {
        $factory->create('flat');
        $factory->create('flat-square');
        $factory->create('for-the-badge');
        $factory->create('plastic');
    }
    $duration = microtime(true) - $start;

    // Should complete in reasonable time (less than 1 second for 40 creations)
    expect($duration)->toBeLessThan(1.0);
});
