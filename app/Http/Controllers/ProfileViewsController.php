<?php

namespace App\Http\Controllers;

use App\Models\ProfileViews;
use App\Http\Requests\ProfileViewRequest;
use App\Services\BadgeGeneratorService;
use App\Services\BadgeRenderService;

/** @package App\Http\Controllers */
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

        $badgeGeneratorService = new BadgeGeneratorService();
        $badge = $badgeGeneratorService->generate($request->username);
        
        $badgeRenderService = new BadgeRenderService();

        $badgeRender = $badgeRenderService->renderBadgeWithCount(
            $request?->label ?? 'Visitors', //+
            $profileView?->visit_count ?? 0,
            $request?->color ?? 'blue',
            $request?->style ?? 'flat',
            $request->style ?? 'default'
        );

// dd($badgeRender);

        return response($badgeRender, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'Cache-Control: max-age=0, no-cache, no-store, must-revalidate',
        ]);;
    }
}
