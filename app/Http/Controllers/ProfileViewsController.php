<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileViewRequest;
use App\Models\ProfileViews;
// use App\Services\BadgeGeneratorService;
use App\Services\BadgeRenderService;

class ProfileViewsController extends Controller
{
    public function index(ProfileViewRequest $request)
    {
        $profileView = ProfileViews::where('username', $request->username)->first();

        if ($profileView?->username) {
            $profileView->incrementCount();
        } else {
            ProfileViews::create(['username' => $request->username, 'visit_count' => 1, 'last_visit' => now()]);
        }

        // $badgeGeneratorService = new BadgeGeneratorService();
        // $badge = $badgeGeneratorService->generate($request->username);

        $badgeRenderService = new BadgeRenderService;

        $badgeRender = $badgeRenderService->renderBadgeWithCount(
            $request?->label ?? 'Views',
            $profileView?->visit_count ?? 0,
            $request?->color ?? 'blue',
            $request?->style ?? 'for-the-badge',
            $request->abbreviated ?? false
        );

        // dd($badgeRender);

        return response($badgeRender, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'Cache-Control: max-age=0, no-cache, no-store, must-revalidate',
        ]);

    }
}
