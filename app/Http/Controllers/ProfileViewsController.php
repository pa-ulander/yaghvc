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
            $count += (int) $safe->base;
        }

        $logo = $safe->logo ?? request()->query('logo');
        $logoSize = $safe->logoSize ?? config('badge.default_logo_size');

        return $this->badgeRenderService->renderBadgeWithCount(
            label: $safe->label ?? config(key: 'badge.default_label'),
            count: $count,
            messageBackgroundFill: $safe->color ?? config(key: 'badge.default_color'),
            badgeStyle: $safe->style ?? config(key: 'badge.default_style'),
            abbreviated: $safe->abbreviated ?? config(key: 'badge.default_abbreviated'),
            labelColor: $safe->labelColor ?? null,
            logoColor: $safe->logoColor ?? null,
            logo: $logo,
            logoSize: $logoSize,
        );
    }

    private function createBadgeResponse(string $badgeRender): Response
    {
        $etag = 'W/"' . sha1($badgeRender) . '"';
        $response = response(content: $badgeRender)
            ->header(key: 'Status', values: '200')
            ->header(key: 'Content-Type', values: 'image/svg+xml')
            // Allow very short caching while requiring revalidation to keep counts fresh.
            ->header(key: 'Cache-Control', values: 'public, max-age=1, s-maxage=1, stale-while-revalidate=5')
            ->header(key: 'ETag', values: $etag);

        // Conditional GET handling
        if (request()->header('If-None-Match') === $etag) {
            $response->setStatusCode(304);
            $response->setContent(null);
        }

        return $response;
    }
}
