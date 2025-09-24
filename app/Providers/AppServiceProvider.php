<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;

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

        // Centralize badge rate limiter definition using configurable limits.
        RateLimiter::for('badge', function (Request $request) {
            $perMinute = (int) config('badge.rate_limit_per_minute', 120);
            if ($perMinute < 1) {
                $perMinute = 60; // fail safe
            }

            // Build key segmentation: IP + username + repository (if provided)
            $username = (string) $request->query('username', 'none');
            // Sanitize to a conservative subset to avoid key bloat
            $username = substr(preg_replace('/[^A-Za-z0-9_-]/', '', $username) ?: 'none', 0, 40);
            $repository = (string) $request->query('repository', 'none');
            $repository = substr(preg_replace('/[^A-Za-z0-9_.-]/', '', $repository) ?: 'none', 0, 100);
            $ip = $request->ip() ?: 'global';
            $baseKey = $ip . '|' . $username . '|' . $repository;

            $limits = [];
            $limits[] = Limit::perMinute($perMinute)->by($baseKey);

            $burstMax = (int) config('badge.rate_limit_burst_max', 0);
            $burstDecay = (int) config('badge.rate_limit_burst_decay_seconds', 10);
            if ($burstMax > 0 && $burstDecay > 0) {
                $limits[] = Limit::perSecond($burstMax, $burstDecay)->by('burst:' . $baseKey);
            }

            return $limits;
        });
    }
}
