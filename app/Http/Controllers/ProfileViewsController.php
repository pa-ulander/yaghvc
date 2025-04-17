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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ValidatedInput;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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
        // Get the identifier for rate limiting (can be IP or any other identifier)
        $key = 'profile-views:' . $request->ip();

        // Get rate limiter configuration
        $maxAttempts = config('cache.limiters.profile-views.max_attempts', 5);
        $decayMinutes = config('cache.limiters.profile-views.decay_minutes', 1);

        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            return response('Too many requests', SymfonyResponse::HTTP_TOO_MANY_REQUESTS)
                ->header('Retry-After', (string) $seconds)
                ->header('X-RateLimit-Limit', (string) $maxAttempts)
                ->header('X-RateLimit-Remaining', '0')
                ->header('X-RateLimit-Reset', (string) now()->addSeconds($seconds)->getTimestamp());
        }

        // Increment the rate limiter counter
        RateLimiter::increment($key, $decayMinutes * 60);

        // /** @var array $safe */
        $safe = $request->safe();
        $profileView = $this->profileViewsRepository->findOrCreate(username: Arr::get(array: $safe, key: 'username'));
        $badgeRender = $this->renderBadge(safe: $safe, profileView: $profileView);

        return $this->createBadgeResponse(badgeRender: $badgeRender, key: $key, maxAttempts: $maxAttempts);
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

    private function createBadgeResponse(string $badgeRender, string $key, int $maxAttempts): Response
    {
        return response(content: $badgeRender)
            ->header(key: 'Status', values: '200')
            ->header(key: 'Content-Type', values: 'image/svg+xml')
            ->header(key: 'Cache-Control', values: 'max-age=0, no-cache, no-store, must-revalidate')
            ->header(key: 'Pragma', values: 'no-cache')
            ->header(key: 'Expires', values: '0')
            ->header(key: 'X-RateLimit-Limit', values: (string) $maxAttempts)
            ->header(key: 'X-RateLimit-Remaining', values: (string) RateLimiter::remaining($key, $maxAttempts));
    }
}
