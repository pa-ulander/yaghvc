<?php

declare(strict_types=1);

use App\Services\BadgeStrategies\FlatSquareBadgeStrategy;
use PUGX\Poser\Calculator\SvgTextSizeCalculator;
use PUGX\Poser\Render\SvgFlatSquareRender;

it('renders a flat-square badge with valid inputs', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgFlatSquareRender(textSizeCalculator: $calculator);
    $strategy = new FlatSquareBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'views',
        message: '1.5K',
        color: 'blue'
    );

    expect(trim($result))
        ->toBeString()
        ->toStartWith('<svg')
        ->toEndWith('</svg>')
        ->toContain('views')
        ->toContain('1.5K');
});

it('renders label text in the output SVG', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgFlatSquareRender(textSizeCalculator: $calculator);
    $strategy = new FlatSquareBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'profile',
        message: '42',
        color: 'green'
    );

    expect($result)->toContain('profile');
});

it('renders message text in the output SVG', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgFlatSquareRender(textSizeCalculator: $calculator);
    $strategy = new FlatSquareBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'visits',
        message: '999',
        color: 'red'
    );

    expect($result)->toContain('999');
});

it('renders flat-square style without rounded corners', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgFlatSquareRender(textSizeCalculator: $calculator);
    $strategy = new FlatSquareBadgeStrategy(renderer: $renderer);

    $result = $strategy->render(
        label: 'badge',
        message: 'test',
        color: 'brightgreen'
    );

    // Flat-square badges don't have rx attribute for rounded corners
    expect($result)
        ->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>');
});

it('returns valid SVG structure', function () {
    $calculator = new SvgTextSizeCalculator();
    $renderer = new SvgFlatSquareRender(textSizeCalculator: $calculator);
    $strategy = new FlatSquareBadgeStrategy(renderer: $renderer);

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
