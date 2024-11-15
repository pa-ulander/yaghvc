<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileViewRequest;
use App\Repositories\ProfileViewsRepository;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Support\ValidatedInput;
use \Illuminate\Support\Arr;
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
        // /** @var array $safe */
        $safe = $request->safe();
        $profileView = $this->profileViewsRepository->findOrCreate(username: Arr::get(array: $safe, key: 'username'));
        $badgeRender = $this->renderBadge(safe: $safe, profileView: $profileView);

        return $this->createBadgeResponse(badgeRender: $badgeRender);
    }

    private function renderBadge(ValidatedInput|array $safe, ProfileViews $profileView): string
    {
        return $this->badgeRenderService->renderBadgeWithCount(
            label: $safe->label ?? config(key: 'badge.default_label'),
            count: $profileView->getCount(username: Arr::get(array: $safe, key: 'username')) ?? 0,
            messageBackgroundFill: $safe->color ?? config(key: 'badge.default_color'),
            badgeStyle: $safe->style ?? config(key: 'badge.default_style'),
            abbreviated: $safe->abbreviated ?? config(key: 'badge.default_abbreviated'),
        );
    }

    private function createBadgeResponse(string $badgeRender): Response
    {
        return response(content: $badgeRender)
        ->header(key: 'Status', values: '200')
        ->header(key: 'Content-Type', values: 'image/svg+xml')
        ->header(key: 'Cache-Control', values: 'max-age=0, no-cache, no-store, must-revalidate')
        ->header(key: 'Pragma', values: 'no-cache')
        ->header(key: 'Expires', values: '0');
    }
}
