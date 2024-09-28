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
        $badgeRenderService = new BadgeRenderService();
        $badge = $badgeGeneratorService->generate($request->username);
        // $badgeRender = $badgeRenderService->renderBadgeWithCount();

        return response($badge, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'Cache-Control: max-age=0, no-cache, no-store, must-revalidate',
        ]);;
    }
}
