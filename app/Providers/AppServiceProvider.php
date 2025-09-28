<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

/** @package App\Providers */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();

        RateLimiter::for(name: 'badge', callback: function (Request $request): array {
            $badgeConfigValue = config('badge', []);
            $badgeConfig = is_array(value: $badgeConfigValue) ? $badgeConfigValue : [];

            $perMinuteDefault = self::configInt(key: 'badge.rate_limit_per_minute', default: 180);
            $perMinute = self::intFromMixed(value: $badgeConfig['rate_limit_per_minute'] ?? null, default: $perMinuteDefault);

            $usernameInput = $request->query('username');
            $username = self::sanitizeKeySegment(
                value: is_string($usernameInput) ? $usernameInput : null,
                pattern: '/[^A-Za-z0-9_-]/',
                fallback: 'none',
                maxLength: 40,
            );
            $repositoryInput = $request->query('repository');
            $repository = self::sanitizeKeySegment(
                value: is_string($repositoryInput) ? $repositoryInput : null,
                pattern: '/[^A-Za-z0-9_.-]/',
                fallback: 'none',
                maxLength: 100,
            );
            $ip = $request->ip() ?: 'global';
            $baseKey = $ip . '|' . $username . '|' . $repository;

            $limits = [Limit::perMinute($perMinute)->by($baseKey)];

            $burstMaxDefault = self::configInt(key: 'badge.rate_limit_burst_max', default: 0);
            $burstDecayDefault = self::configInt(key: 'badge.rate_limit_burst_decay_seconds', default: 10);
            $burstMax = self::intFromMixed(value: $badgeConfig['rate_limit_burst_max'] ?? null, default: $burstMaxDefault);
            $burstDecay = self::intFromMixed(value: $badgeConfig['rate_limit_burst_decay_seconds'] ?? null, default: $burstDecayDefault);
            if ($burstMax > 0 && $burstDecay > 0) {
                $limits[] = Limit::perSecond(maxAttempts: $burstMax, decaySeconds: $burstDecay)->by(key: 'burst:' . $baseKey);
            }

            return $limits;
        });
    }

    /**
     * Normalize a request segment for use in rate-limit keys.
     */
    private static function sanitizeKeySegment(?string $value, string $pattern, string $fallback, int $maxLength): string
    {
        $value ??= $fallback;
        $value = preg_replace($pattern, '', $value) ?: $fallback;

        return substr($value, 0, $maxLength);
    }

    private static function configInt(string $key, int $default): int
    {
        $value = config($key, $default);
        return self::intFromMixed(value: $value, default: $default);
    }

    private static function intFromMixed(mixed $value, int $default): int
    {
        if (is_int(value: $value)) {
            return $value;
        }
        if (is_string(value: $value) && is_numeric(value: $value)) {
            return (int) $value;
        }
        if (is_float(value: $value)) {
            return (int) $value;
        }
        return $default;
    }
}
