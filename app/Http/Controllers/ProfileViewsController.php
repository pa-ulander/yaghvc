<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileViewsRequest;
use App\Models\ProfileViews;
use App\Repositories\ProfileViewsRepository;
use App\Services\BadgeRenderService;
use App\ValueObjects\BadgeRequest;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\ValidatedInput;

/**
 * Controller for rendering GitHub profile/repository visitor count badges.
 *
 * @package App\Http\Controllers
 */
class ProfileViewsController extends Controller
{
    public function __construct(
        private readonly BadgeRenderService $badgeRenderService,
        private readonly ProfileViewsRepository $profileViewsRepository,
    ) {
        // Property promotion with readonly eliminates need for manual assignments.
    }

    /**
     * Handle badge request and return SVG badge response.
     *
     * @param ProfileViewsRequest $request
     * @return ResponseFactory|Response
     */
    public function index(ProfileViewsRequest $request): ResponseFactory|Response
    {
        $safe = $request->safe();
        $safeData = $this->normaliseValidatedInput($safe);

        // Create BadgeRequest value object from validated data
        $badgeRequest = BadgeRequest::fromValidatedData($safeData);

        $profileView = $this->profileViewsRepository->findOrCreate(
            username: $badgeRequest->profile->username,
            repository: $badgeRequest->profile->repository
        );

        $badgeRender = $this->renderBadge(badgeRequest: $badgeRequest, profileView: $profileView);

        return $this->createBadgeResponse(badgeRender: $badgeRender);
    }

    /**
     * Render badge using BadgeRequest value object.
     *
     * @param BadgeRequest $badgeRequest
     * @param ProfileViews $profileView
     * @return string
     */
    private function renderBadge(BadgeRequest $badgeRequest, ProfileViews $profileView): string
    {
        $count = $profileView->getCount(
            username: $badgeRequest->profile->username,
            repository: $badgeRequest->profile->repository
        );

        // Add base count if provided
        $count += $badgeRequest->baseCount;

        // Handle logo from query string if not in validated data
        $logo = $badgeRequest->config->logo;
        if ($logo === null) {
            $logo = $this->queryString('logo');
        }

        return $this->badgeRenderService->renderBadgeWithCount(
            label: $badgeRequest->config->label,
            count: $count,
            messageBackgroundFill: $badgeRequest->config->color,
            badgeStyle: $badgeRequest->config->style,
            abbreviated: $badgeRequest->config->abbreviated,
            labelColor: $badgeRequest->config->labelColor,
            logoColor: $badgeRequest->config->logoColor,
            logo: $logo,
            logoSize: $badgeRequest->config->logoSize,
        );
    }

    private function createBadgeResponse(string $badgeRender): Response
    {
        $etag = 'W/"' . sha1(string: $badgeRender) . '"';
        $response = response(content: $badgeRender)
            ->header(key: 'Status', values: '200')
            ->header(key: 'Content-Type', values: 'image/svg+xml')
            ->header(key: 'Cache-Control', values: 'public, max-age=1, s-maxage=1, stale-while-revalidate=5')
            ->header(key: 'ETag', values: $etag);

        if (request()->header('If-None-Match') === $etag) {
            $response->setStatusCode(code: 304);
            $response->setContent(content: null);
        }

        return $response;
    }

    /**
     * @param ValidatedInput|array<string,mixed> $safe
     * @return array<string,mixed>
     */
    private function normaliseValidatedInput(ValidatedInput|array $safe): array
    {
        return $safe instanceof ValidatedInput ? $safe->toArray() : $safe;
    }

    /**
     * Get query string parameter safely.
     *
     * @param string $key
     * @return string|null
     */
    private function queryString(string $key): ?string
    {
        $value = request()->query($key);
        return is_string($value) ? $value : null;
    }
}
