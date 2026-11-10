<?php

declare(strict_types=1);

use App\Factories\BadgeRendererFactory;
use App\Services\BadgeRenderService;

it('preserves badge geometry and paints logos above backgrounds across styles', function (string $style, ?string $labelColor, ?string $logoSize) {
    $service = new BadgeRenderService(new BadgeRendererFactory());
    $render = fn (?string $logo): string => $service->renderBadgeWithCount(
        label: 'Views',
        count: 123,
        messageBackgroundFill: 'blue',
        badgeStyle: $style,
        abbreviated: false,
        labelColor: $labelColor,
        logo: $logo,
        logoSize: $logoSize,
    );
    $original = new DOMDocument();
    $original->loadXML($render(null));
    $badge = new DOMDocument();
    $badge->loadXML($render('github'));
    $xpath = new DOMXPath($badge);
    $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
    $image = $badge->getElementsByTagName('image')->item(0);

    expect($image)->toBeInstanceOf(DOMElement::class);
    $segment = 10 + (float) $image->getAttribute('width');
    $originalWidth = (float) $original->documentElement->getAttribute('width');
    $newWidth = (float) $badge->documentElement->getAttribute('width');
    expect($newWidth)->toBe($originalWidth + $segment);
    expect($xpath->query('following::svg:rect', $image)->length)->toBe(0);
    expect((float) $image->getAttribute('y') + (float) $image->getAttribute('height'))
        ->toBeLessThanOrEqual((float) $badge->documentElement->getAttribute('height'));

    $originalRects = $original->getElementsByTagName('rect');
    $rects = $badge->getElementsByTagName('rect');
    $originalStatus = $originalRects->item($style === 'plastic' ? 1 : ($style === 'for-the-badge' ? 1 : 2));
    $statusX = (float) $originalStatus->getAttribute('x');
    foreach ($originalRects as $index => $rect) {
        $shifted = $rects->item($index);
        $x = (float) $rect->getAttribute('x');
        $width = (float) $rect->getAttribute('width');
        if ($x === $statusX) {
            expect((float) $shifted->getAttribute('x'))->toBe($x + $segment);
            expect((float) $shifted->getAttribute('width'))->toBe($width);
        } else {
            expect((float) $shifted->getAttribute('width'))->toBe($width + $segment);
        }
        expect((float) $shifted->getAttribute('x') + (float) $shifted->getAttribute('width'))
            ->toBeLessThanOrEqual($newWidth);
    }

    foreach ($original->getElementsByTagName('text') as $index => $text) {
        $shifted = $badge->getElementsByTagName('text')->item($index);
        expect((float) $shifted->getAttribute('x'))->toBe((float) $text->getAttribute('x') + $segment);
    }
    expect($rects->item($style === 'flat' || $style === 'flat-square' ? 1 : 0)->getAttribute('fill'))
        ->toBe($labelColor === null ? '#555' : '#e05d44');
})->with(['flat', 'flat-square', 'plastic', 'for-the-badge'])
    ->with([null, 'red'])
    ->with([null, 'auto', '8', '32']);

it('paints SVG and raster data URI logos above backgrounds', function (string $style, string $logo) {
    $service = new BadgeRenderService(new BadgeRendererFactory());
    $svg = $service->renderBadgeWithCount(
        label: 'Views',
        count: 123,
        messageBackgroundFill: 'blue',
        badgeStyle: $style,
        abbreviated: false,
        logo: $logo,
        logoSize: 'auto',
    );
    $badge = new DOMDocument();
    $badge->loadXML($svg);
    $xpath = new DOMXPath($badge);
    $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
    $image = $badge->getElementsByTagName('image')->item(0);

    expect($image)->toBeInstanceOf(DOMElement::class);
    expect($xpath->query('following::svg:rect', $image)->length)->toBe(0);
})->with(['flat', 'flat-square', 'plastic', 'for-the-badge'])
    ->with([
        'svg' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="10"><rect width="20" height="10" fill="white"/></svg>'),
        'png' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PvSxNwAAAABJRU5ErkJggg==',
    ]);
