<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;

it('throttles after configured limit per IP', function (): void {
    Config::set('badge.rate_limit_per_minute', 5);

    // Warm up and hit until limit
    for ($i = 1; $i <= 5; $i++) {
        test()->get('/?username=alice')->assertOk();
    }

    // Next request should be throttled
    test()->get('/?username=alice')->assertStatus(429);
});

it('separates buckets by IP address', function (): void {
    Config::set('badge.rate_limit_per_minute', 2);

    // Default IP bucket
    test()->get('/?username=alice')->assertOk();
    test()->get('/?username=alice')->assertOk();
    test()->get('/?username=alice')->assertStatus(429);

    // Different IP bucket should have its own allowance
    $server = ['REMOTE_ADDR' => '203.0.113.5'];
    test()->get('/?username=alice', $server)->assertOk();
    test()->get('/?username=alice', $server)->assertOk();
    test()->get('/?username=alice', $server)->assertStatus(429);
});

it('segments limits by username and repository', function (): void {
    Config::set('badge.rate_limit_per_minute', 2);

    // Two requests for user only
    test()->get('/?username=seguser')->assertOk();
    test()->get('/?username=seguser')->assertOk();
    test()->get('/?username=seguser')->assertStatus(429);

    // Repository variant should have independent bucket
    test()->get('/?username=seguser&repository=repo1')->assertOk();
    test()->get('/?username=seguser&repository=repo1')->assertOk();
    test()->get('/?username=seguser&repository=repo1')->assertStatus(429);
});

it('applies burst limiter when configured', function (): void {
    Config::set('badge.rate_limit_per_minute', 1000); // effectively disable minute limit influence here
    Config::set('badge.rate_limit_burst_max', 3);
    Config::set('badge.rate_limit_burst_decay_seconds', 5);

    test()->get('/?username=burst')->assertOk();
    test()->get('/?username=burst')->assertOk();
    test()->get('/?username=burst')->assertOk();
    test()->get('/?username=burst')->assertStatus(429); // 4th within burst window triggers
});
