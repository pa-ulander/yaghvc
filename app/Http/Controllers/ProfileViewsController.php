<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileViewRequest;
use App\Models\ProfileViews;
use App\Services\BadgeRenderService;

class ProfileViewsController extends Controller
{
    public function index(ProfileViewRequest $request)
    {
        $profileView = ProfileViews::where(column: 'username', operator: $request->username)->first();

        if ($profileView?->username) {
            $profileView->incrementCount();
        } else {
            ProfileViews::create(attributes: ['username' => $request->username, 'visit_count' => 1, 'last_visit' => now()]);
        }

        $badgeRenderService = new BadgeRenderService;

        $badgeRender = $badgeRenderService->renderBadgeWithCount(
            label: $request?->getBadgeLabel() ?? 'Viewsies',
            count: $profileView?->getCount($profileView?->username) ?? 0,
            messageBackgroundFill: $request?->getBadgeColor() ?? 'blue',
            badgeStyle: $request?->getBadgeStyle() ?? 'for-the-badge',
            abbreviated: $request?->getAbbreviated() ?? false
        );

        // dd($badgeRender);

        return response($badgeRender, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'Cache-Control: max-age=0, no-cache, no-store, must-revalidate',
        ]);

    }
}
