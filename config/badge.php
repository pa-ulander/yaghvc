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
];
