<?php

/**
 * Defaults for badge render
 */
return [
    'default_label' => 'Visits',
    'default_color' => 'blue',
    'default_style' => 'for-the-badge',
    'default_base' => 0,
    'default_abbreviated' => false,
    'default_label_color' => 'blue',
    'default_logo' => null,

    // Logo related defaults / limits
    'default_logo_size' => '16', // numeric string or 'auto'
    'logo_max_bytes' => 10000, // ~10KB decoded payload cap
    'logo_max_dimension' => 32, // max width/height for raster; svg intrinsic clamp
    'logo_cache_ttl' => 3600, // seconds

    // Enable verbose geometry / logo debug logging (disabled by default for perf)
    'debug_logging' => env('BADGE_DEBUG_LOG', false),

    // Requests per minute per IP for the badge endpoint (throttle:badge)
    'rate_limit_per_minute' => env('BADGE_RATE_LIMIT_PER_MINUTE', 180),

    // Optional burst limiting (short window). When enabled (>0), a secondary limiter
    // caps the number of requests within the defined number of seconds for the same key.
    'rate_limit_burst_max' => env('BADGE_RATE_LIMIT_BURST_MAX', 0), // 0 disables
    'rate_limit_burst_decay_seconds' => env('BADGE_RATE_LIMIT_BURST_DECAY', 10),
];
