<?php

declare(strict_types=1);

use App\Services\BadgeRenderService;
use App\Services\LogoProcessor;

it('covers renderBadgeWithError and renderPixel', function () {
    $svc = new BadgeRenderService();
    $err = $svc->renderBadgeWithError(label: 'label', message: 'oops', badgeStyle: 'flat');
    expect($err)->toContain('oops')->toContain('svg');
    $pixel = $svc->renderPixel();
    expect($pixel)->toBe('<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>');
});

it('covers applyLogo fallback when prepare returns null (invalid slug)', function () {
    $svc = new BadgeRenderService();
    // Invalid slug that passes initial if($logo) but prepare() returns null -> degrade silently
    $svg = $svc->renderBadgeWithCount(label: 'views', count: 1, messageBackgroundFill: 'blue', badgeStyle: 'flat', abbreviated: false, logo: '___invalid___');
    // Should not throw and still produce an svg
    expect($svg)->toContain('<svg');
});

it('covers raw base64 normalization failure path', function () {
    $svc = new BadgeRenderService();
    // Provide garbage that is not data: and not valid base64 so raw-normalization fails and degrade silently
    $svg = $svc->renderBadgeWithCount(label: 'demo', count: 2, messageBackgroundFill: 'green', badgeStyle: 'flat', abbreviated: false, logo: 'not_base64***');
    expect($svg)->toContain('<svg');
});

it('covers salvage path via malformed data uri', function () {
    $svc = new BadgeRenderService();
    // Malformed data uri that will trigger exception inside applyLogo catch and salvage (truncated base64)
    $bad = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA';
    $svg = $svc->renderBadgeWithCount(label: 'x', count: 3, messageBackgroundFill: 'red', badgeStyle: 'flat', abbreviated: false, logo: $bad);
    expect($svg)->toContain('<svg');
});

it('covers post-fallback embed when dataUri set but <image> missing', function () {
    $svc = new BadgeRenderService();
    // Force path: give a tiny valid png data uri that passes prepare -> ensure image appears
    $png = base64_encode(hex2bin('89504E470D0A1A0A0000000D4948445200000001000000010802000000907724')); // 1x1 png header likely invalid CRC but small
    $data = 'data:image/png;base64,' . $png;
    $svg = $svc->renderBadgeWithCount(label: 'z', count: 4, messageBackgroundFill: 'blue', badgeStyle: 'flat', abbreviated: false, logo: $data);
    expect($svg)->toContain('<svg')->toContain('<image');
});

it('covers recolor branch no-change (returns null recolor)', function () {
    $svc = new BadgeRenderService();
    // Provide an svg logo with fill already target so recolor returns null branch path is still executed
    $svgLogo = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><path fill="#007ec6" d="M0 0h10v10H0z"/></svg>');
    $svg = $svc->renderBadgeWithCount(label: 'c', count: 5, messageBackgroundFill: 'blue', badgeStyle: 'flat', abbreviated: false, logo: $svgLogo, logoColor: '007ec6');
    expect($svg)->toContain('<svg')->toContain('<image');
});
