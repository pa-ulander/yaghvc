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
use Illuminate\Support\Facades\RateLimiter;
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
        $profileView = $this->profileViewsRepository->findOrCreate(username: Arr::get(array: $safe, key: 'username'));
        $badgeRender = $this->renderBadge(safe: $safe, profileView: $profileView);

        $key = 'profile-views:' . md5($request->input('username') . $request->input('repository'));
        $maxAttempts = Config::get('cache.limiters.profile-views.max_attempts', 5);

        // For test compatibility, manually hit the rate limiter
        RateLimiter::hit($key);

        return $this->createBadgeResponse(
            badgeRender: $badgeRender,
            rateLimitKey: $key,
            maxAttempts: config('cache.limiters.profile-views.max_attempts')
        );
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

    private function createBadgeResponse(
        string $badgeRender,
        ?string $rateLimitKey = null,
        ?int $maxAttempts = null
    ): Response {
        $response = response(content: $badgeRender)
            ->header(key: 'Status', values: '200')
            ->header(key: 'Content-Type', values: 'image/svg+xml')
            ->header(key: 'Cache-Control', values: 'max-age=0, no-cache, no-store, must-revalidate')
            ->header(key: 'Pragma', values: 'no-cache')
            ->header(key: 'Expires', values: '0');

        // Add rate limiting headers for backward compatibility with tests
        if ($rateLimitKey !== null && $maxAttempts !== null) {
            $remainingAttempts = RateLimiter::remaining($rateLimitKey, $maxAttempts);

            // If rate-limited, change response status code to 429 (too many requests)
            if ($remainingAttempts <= 0) {
                $response->setStatusCode(429);
                $response->header('Retry-After', (string) RateLimiter::availableIn($rateLimitKey));
            }

            $response->header('X-RateLimit-Limit', (string) $maxAttempts);
            $response->header('X-RateLimit-Remaining', (string) max(0, $remainingAttempts));
        }

        return $response;
    }
}
