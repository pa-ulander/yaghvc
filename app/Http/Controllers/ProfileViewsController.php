<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfileViews;
use App\Http\Requests\ProfileViewRequest;
use App\Services\BadgeGeneratorService;
use App\Services\BadgeRenderService;

class ProfileViewsController extends Controller
{

    public function index(ProfileViewRequest $request)
    {
        $profileView = new ProfileViews;

        $profileView->username = $request->username;
        $profileView->save();

        $badgeGeneratorService = new BadgeGeneratorService();
        $badgeRenderService = new BadgeRenderService();
        $badge = $badgeGeneratorService->generate($request->username);
        // $badgeRender = $badgeRenderService->renderBadgeWithCount();

        // should return code to generate the badge
        return response($badge, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'Cache-Control: max-age=0, no-cache, no-store, must-revalidate',
        ]);;
    }
}
