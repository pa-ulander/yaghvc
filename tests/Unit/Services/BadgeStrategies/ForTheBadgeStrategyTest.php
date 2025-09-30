<?php

declare(strict_types=1);

use App\Services\BadgeStrategies\ForTheBadgeStrategy;
use PUGX\Poser\Calculator\SvgTextSizeCalculator;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;

it('renders a for-the-badge style badge with valid inputs', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgForTheBadgeRenderer(textSizeCalculator: $calculator);
    $strategy = new ForTheBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'views',
        message: '1.5K',
        color: 'blue'
    );

    expect(trim($result))
        ->toBeString()
        ->toStartWith('<svg')
        ->toEndWith('</svg>')
        ->toContain('VIEWS') // for-the-badge uppercases text
        ->toContain('1.5K');
});

it('renders label text in uppercase', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgForTheBadgeRenderer(textSizeCalculator: $calculator);
    $strategy = new ForTheBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'profile',
        message: '42',
        color: 'green'
    );

    expect($result)->toContain('PROFILE'); // Uppercased by the style
});

it('renders message text in the output SVG', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgForTheBadgeRenderer(textSizeCalculator: $calculator);
    $strategy = new ForTheBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'visits',
        message: '999',
        color: 'red'
    );

    expect($result)->toContain('999');
});

it('renders larger badge with bold style', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgForTheBadgeRenderer(textSizeCalculator: $calculator);
    $strategy = new ForTheBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'badge',
        message: 'test',
        color: 'brightgreen'
    );

    // for-the-badge style has larger height (28px instead of 20px)
    expect($result)
        ->toBeString()
        ->toContain('height="28"');
});

it('returns valid SVG structure', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgForTheBadgeRenderer(textSizeCalculator: $calculator);
    $strategy = new ForTheBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'test',
        message: 'badge',
        color: 'blue'
    );

    expect(trim($result))
        ->toStartWith('<svg')
        ->toEndWith('</svg>')
        ->toContain('xmlns="http://www.w3.org/2000/svg"');
});
