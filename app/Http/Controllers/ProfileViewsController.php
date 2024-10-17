<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileViewRequest;
use App\Repositories\ProfileViewsRepository;
use App\Models\ProfileViews;
use App\Services\BadgeRenderService;
use Illuminate\Http\Response;
use Illuminate\Contracts\Routing\ResponseFactory;

class ProfileViewsController extends Controller
{
    public function __construct(
        private BadgeRenderService $badgeRenderService,
        private ProfileViewsRepository $profileViewsRepository
    ) {
        $this->badgeRenderService = $badgeRenderService;
        $this->profileViewsRepository = $profileViewsRepository;
    }

    public function index(ProfileViewRequest $request): ResponseFactory|Response
    {
        $safe = $request->safe();
        $profileView = $this->profileViewsRepository->findOrCreate(username: $safe->username);

        $badgeRender = $this->renderBadge(safe: $safe, profileView: $profileView);

        return $this->createBadgeResponse(badgeRender: $badgeRender);
    }

    private function renderBadge($safe, ProfileViews $profileView): string
    {
        // dd(config(key: 'badge.default_label'));
        return $this->badgeRenderService->renderBadgeWithCount(
            label: $safe->label ?? config(key: 'badge.default_label'),
            count: $profileView->getCount(username: $safe->username) ?? 0,
            messageBackgroundFill: $safe->color ?? config(key: 'badge.default_color'),
            badgeStyle: $safe->style ?? config(key: 'badge.default_style'),
            abbreviated: $safe->abbreviated ?? config(key: 'badge.default_abbreviated'),
        );
    }

    private function createBadgeResponse(string $badgeRender): Response
    {
        return response(content: $badgeRender, status: 200, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }
}
