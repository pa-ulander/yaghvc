<?php

declare(strict_types=1);

use App\Services\BadgeRenderService;
use Illuminate\Support\Facades\Log;

it('emits debug logs when BADGE_DEBUG_LOG=1', function () {
    putenv('BADGE_DEBUG_LOG=1');
    Log::shouldReceive('debug')->atLeast()->once();
    $svc = new BadgeRenderService();
    $png = 'data:image/png;base64,' . base64_encode(random_bytes(16));
    $badge = $svc->renderBadgeWithCount(label: 'views', count: 1, messageBackgroundFill: 'blue', badgeStyle: 'flat', abbreviated: false, logo: $png);
    expect($badge)->toContain('<svg');
});

it('suppresses debug logs when BADGE_DEBUG_LOG=0', function () {
    putenv('BADGE_DEBUG_LOG=0');
    Log::shouldReceive('debug')->never();
    $svc = new BadgeRenderService();
    $png = 'data:image/png;base64,' . base64_encode(random_bytes(16));
    $badge = $svc->renderBadgeWithCount(label: 'views', count: 5, messageBackgroundFill: 'blue', badgeStyle: 'flat', abbreviated: false, logo: $png);
    expect($badge)->toContain('<svg');
});
