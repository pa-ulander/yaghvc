<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileViewsRequest;
use App\Models\ProfileViews;
use App\Repositories\ProfileViewsRepository;
use App\Services\BadgeRenderService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\ValidatedInput;
use Webmozart\Assert\Assert;

/** @package App\Http\Controllers */
class ProfileViewsController extends Controller
{
    public function __construct(
        private readonly BadgeRenderService $badgeRenderService,
        private readonly ProfileViewsRepository $profileViewsRepository,
    ) {
        // Property promotion with readonly eliminates need for manual assignments.
    }

    public function index(ProfileViewsRequest $request): ResponseFactory|Response
    {
        $safe = $request->safe();
        $safeData = $this->normaliseValidatedInput($safe);

        Assert::keyExists($safeData, 'username');
        Assert::string($safeData['username']);
        $username = $safeData['username'];

        $repository = $safeData['repository'] ?? null;
        if ($repository !== null) {
            Assert::string($repository);
        }

        $profileView = $this->profileViewsRepository->findOrCreate(username: $username, repository: $repository);
        $badgeRender = $this->renderBadge(safe: $safeData, profileView: $profileView);

        return $this->createBadgeResponse(badgeRender: $badgeRender);
    }

    /**
     * Render badge using validated data.
     *
     * @param array<string,mixed> $safe
     */
    private function renderBadge(array $safe, ProfileViews $profileView): string
    {
        Assert::keyExists($safe, 'username');
        Assert::string($safe['username']);
        $username = $safe['username'];

        $repository = $safe['repository'] ?? null;
        if ($repository !== null) {
            Assert::string($repository);
        }

        $count = $profileView->getCount(username: $username, repository: $repository);

        if (array_key_exists('base', $safe)) {
            $base = $safe['base'];
            if (is_int($base)) {
                $count += $base;
            } elseif (is_string($base) && $base !== '') {
                $count += (int) $base;
            }
        }

        $logo = $this->stringFromSafe($safe, 'logo');
        if ($logo === null) {
            $logo = $this->queryString('logo');
        }

        $logoSize = $this->stringFromSafe($safe, 'logoSize') ?? $this->stringConfig('badge.default_logo_size', '16');

        $label = $this->stringFromSafe($safe, 'label') ?? $this->stringConfig('badge.default_label', 'Visits');
        $color = $this->stringFromSafe($safe, 'color') ?? $this->stringConfig('badge.default_color', 'blue');
        $style = $this->stringFromSafe($safe, 'style') ?? $this->stringConfig('badge.default_style', 'for-the-badge');
        $abbreviated = $this->boolFromSafe($safe, 'abbreviated', $this->boolConfig('badge.default_abbreviated', false));

        $labelColor = $this->stringFromSafe($safe, 'labelColor');
        $logoColor = $this->stringFromSafe($safe, 'logoColor');

        return $this->badgeRenderService->renderBadgeWithCount(
            label: $label,
            count: $count,
            messageBackgroundFill: $color,
            badgeStyle: $style,
            abbreviated: $abbreviated,
            labelColor: $labelColor,
            logoColor: $logoColor,
            logo: $logo,
            logoSize: $logoSize,
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
     * @param array<string,mixed> $safe
     */
    private function stringFromSafe(array $safe, string $key): ?string
    {
        if (! array_key_exists($key, $safe)) {
            return null;
        }
        $value = $safe[$key];
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        return null;
    }

    /**
     * @param array<string,mixed> $safe
     */
    private function boolFromSafe(array $safe, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $safe)) {
            return $default;
        }
        $value = $safe[$key];
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $normalized = strtolower($value);
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }
        if (is_int($value)) {
            return $value === 1;
        }
        return $default;
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config($key, $default);
        return is_string($value) ? $value : $default;
    }

    private function boolConfig(string $key, bool $default): bool
    {
        $value = config($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $normalized = strtolower($value);
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }
        if (is_int($value)) {
            return $value === 1;
        }
        return $default;
    }

    private function queryString(string $key): ?string
    {
        $value = request()->query($key);
        return is_string($value) ? $value : null;
    }
}
