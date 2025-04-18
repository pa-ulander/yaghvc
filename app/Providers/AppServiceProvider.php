<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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

        // Configure the profile-views rate limiter
        RateLimiter::for('profile-views', function (Request $request) {
            $maxAttempts = config('cache.limiters.profile-views.max_attempts', 5);
            $decayMinutes = config('cache.limiters.profile-views.decay_minutes', 1);

            return Limit::perMinute($maxAttempts)
                ->by($request->ip());
        });
    }
}
