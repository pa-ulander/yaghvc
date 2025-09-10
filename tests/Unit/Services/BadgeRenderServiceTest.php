<?php

use App\Services\BadgeRenderService;
use PUGX\Poser\Poser;

beforeEach(function () {
    $this->badgeRenderService = new BadgeRenderService();
});

it('renders badge with count', function () {
    $result = $this->badgeRenderService->renderBadgeWithCount('Views', 1000, 'blue', 'flat', false, null, null);

    expect($result)->toBeString();
    expect($result)->toContain('<svg');
    expect($result)->toContain('Views');
    expect($result)->toContain('1,000');
});

it('renders badge with abbreviated count', function () {
    $result = $this->badgeRenderService->renderBadgeWithCount('Views', 1500, 'green', 'flat-square', true, null, null);

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
        $result = $this->badgeRenderService->renderBadgeWithCount('Test', 100, $color, 'flat', false, null, null);
        expect($result)->toContain($color);
    }
});

it('applies label color correctly', function () {
    $result = $this->badgeRenderService->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, 'red', null);
    expect($result)->toContain('fill="#e05d44"'); // red color
});

it('handles named label colors', function () {
    $result = $this->badgeRenderService->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, 'green', null);
    expect($result)->toContain('fill="#97ca00"'); // green color
});

it('handles hex label colors', function () {
    $result = $this->badgeRenderService->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, 'ff0000', null);
    expect($result)->toContain('fill="#ff0000"'); // red color
});

it('handles logo parameter without errors', function () {
    $base64Logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    $result = $this->badgeRenderService->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, $base64Logo);
    expect($result)->toBeString();
    expect($result)->toContain('<svg');
});

it('handles logo base64 where plus signs may be spaces from query decoding', function () {
    // Create a base64 string containing + characters artificially (small red dot PNG already has some, but ensure)
    $original = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAACmElEQVQokUWSa0iTcRTGn//26u4b6ZQ0U8lKMqykwPpgZVBEHyLp8jEoIZJADCQ0iCiStIwuZmHRioIuroQss2VkrkIrdeFckiZqdhctTXPOve8Tr7M6X8/zO+fwPEfIwy7IwQA0GgExGYQwyhCmMLRX1z2hJCJSN+xZgqAZnPgCaAUQ0EHICjSYLlKBCDdNQb7HLmeRoy3zQFnzYk/1WTckGUIXCVD+Kw+BpAxtuBXCpkN7bdXt/JL3W3J3xuHg3iTsL/NkNFWVPoWkQOj/wxooCrRhFgiTjI4n9ZVHHQObjxVEY8UGIi1zEhVFCahwdq5qvn+hHkKC0EcBigxwvAnkW3ge7L6TMi+VztOLOOKOY8ulKL68GM2emnjeLF3AZSlz2FCZ6yaHwLGv6pkv8MyxsUoHLcsLwBuHwE0rtdy2UuLWNTpmpkkszQEfnAPDAd47tbaB7NaJR+eXujfmtGTUXgFWp5uwPd8Oi1GBJEmwWYlP34L4PSFw7chPeD+MYnkWUVmy0CeNfe5N8ANIjNWpNmHzqklYrDIGRwRm2gXsM/xofRMOf1AgcbYOAfgxMvgxCmS9+dbh5A6VarxuIMdBDoJ0g+vSreytNpAEux7qqWrK82I+kC2xYOAzyFbz5QNJPrXhdRo4XK/n3WILkxPsbKqwsr8xBB3PjukhGyJJv+qqB+QvkN0mR2Fim5pU1hobzxTYOPbcyJoTNpoAlu6wdZKvIslR0O9VXe0Clc5p2Ge4WDh36ux3ThM/1RqnNhXvilU32cjvINtAf4cKdkzlSHpBTqgNY11JfLtFA+o14NU8Wx/piggNfg2yGVR8EF9/dP37PyCIoDQLs8z9hmv71nsC4wFz9klX2tD4/AEG+gBoQ7KghD8MZ2xdnt7s7wAAAABJRU5ErkJggg==';
    // Simulate what would happen if '+' became ' ' in query decoding (already handled now)
    $withSpaces = str_replace('+', ' ', $original);
    $service = new BadgeRenderService();
    $result = $service->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, $withSpaces);
    expect($result)->toBeString();
    expect($result)->toContain('<svg');
});
