<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileViewsRequest;
use App\Models\ProfileViews;
use App\Repositories\ProfileViewsRepository;
use App\Services\BadgeRenderService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ValidatedInput;

class ProfileViewsController extends Controller
{
    public function __construct(
        private BadgeRenderService $badgeRenderService,
        private ProfileViewsRepository $profileViewsRepository
    ) {
        $this->badgeRenderService = $badgeRenderService;
        $this->profileViewsRepository = $profileViewsRepository;
    }

    public function index(ProfileViewsRequest $request): ResponseFactory|Response
    {
        $safe = $request->safe();
        $username = Arr::get(array: $safe, key: 'username');
        $repository = Arr::get(array: $safe, key: 'repository');

        $profileView = $this->profileViewsRepository->findOrCreate(username: $username, repository: $repository);
        $badgeRender = $this->renderBadge(safe: $safe, profileView: $profileView);

        return $this->createBadgeResponse($badgeRender);
    }

    private function renderBadge(ValidatedInput|array $safe, ProfileViews $profileView): string
    {
        $username = Arr::get(array: $safe, key: 'username');
        $repository = Arr::get(array: $safe, key: 'repository');

        $count = $profileView->getCount(username: $username, repository: $repository) ?? 0;

        // Add base count if provided
        if (isset($safe->base) && is_numeric($safe->base)) {
            $count += (int)$safe->base;
        }

        return $this->badgeRenderService->renderBadgeWithCount(
            label: $safe->label ?? config(key: 'badge.default_label'),
            count: $count,
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
