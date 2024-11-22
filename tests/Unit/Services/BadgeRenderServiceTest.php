<?php

use App\Services\BadgeRenderService;
use PUGX\Poser\Poser;

beforeEach(function () {
    $this->badgeRenderService = new BadgeRenderService();
});

it('renders badge with count', function () {
    $result = $this->badgeRenderService->renderBadgeWithCount('Views', 1000, 'blue', 'flat', false);

    expect($result)->toBeString();
    expect($result)->toContain('<svg');
    expect($result)->toContain('Views');
    expect($result)->toContain('1,000');
});

it('renders badge with abbreviated count', function () {
    $result = $this->badgeRenderService->renderBadgeWithCount('Views', 1500, 'green', 'flat-square', true);

    expect($result)->toBeString();
    expect($result)->toContain('<svg');
    expect($result)->toContain('Views');
    expect($result)->toContain('1.5K');
});

it('renders badge with error', function () {
    $result = $this->badgeRenderService->renderBadgeWithError('Error', 'Not Found', 'plastic');
    expect($result)->toBeString();
    expect($result)->toContain('<svg');
    expect($result)->toContain('Error');
    expect($result)->toContain('Not Found');
    expect($result)->toContain('#e05d44');
});

it('renders pixel', function () {
    $result = $this->badgeRenderService->renderPixel();
    expect($result)->toBe('<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>');
});

it('formats number without abbreviation', function () {
    $badgeRenderService = new BadgeRenderService();
    $reflection = new ReflectionClass($badgeRenderService);
    $method = $reflection->getMethod('formatNumber');
    $method->setAccessible(true);

    $result = $method->invokeArgs($badgeRenderService, [1234567, false]);
    expect($result)->toBe('1,234,567');
});

it('formats abbreviated number', function () {
    $testCases = [
        [999, '999'],
        [1000, '1K'],
        [1500, '1.5K'],
        [1000000, '1M'],
        [1500000, '1.5M'],
        [1000000000, '1B'],
        [1500000000000, '1.5T'],
    ];

    foreach ($testCases as [$input, $expected]) {
        $result = $this->badgeRenderService->formatAbbreviatedNumber($input);
        expect($result)->toBe($expected);
    }
});

it('creates Poser instance with correct renderers', function () {
    $reflection = new ReflectionClass($this->badgeRenderService);
    $property = $reflection->getProperty('poser');
    $property->setAccessible(true);

    $poser = $property->getValue($this->badgeRenderService);
    expect($poser)->toBeInstanceOf(Poser::class);

    $styles = ['plastic', 'flat', 'flat-square', 'for-the-badge'];
    foreach ($styles as $style) {
        $badge = $poser->generate('Subject', 'Status', 'blue', $style);
        expect($badge)->toBeInstanceOf(\PUGX\Poser\Image::class);
    }
});


it('uses correct color', function () {
    // 'red', 'green', 'blue', 'yellow'
    $colors = ['#e05d44', '#97ca00', '#007ec6', '#dfb317'];

    foreach ($colors as $color) {
        $result = $this->badgeRenderService->renderBadgeWithCount('Test', 100, $color, 'flat', false);
        expect($result)->toContain($color);
    }
});
