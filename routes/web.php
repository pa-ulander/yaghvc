<?php

use App\Http\Controllers\ProfileViewsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

// Define a lightweight rate limiter (per IP) for the badge endpoint to mitigate abuse.
RateLimiter::for('badge', function (Request $request) {
    // Allow bursts up to 60 per minute per IP (can be tuned via env later)
    return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip() ?: 'global');
});

Route::middleware('throttle:badge')->get('/', [ProfileViewsController::class, 'index']);
