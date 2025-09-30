<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Factories\BadgeRendererFactory;
use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class AppServiceProviderTest extends TestCase
{
    use WithFaker;

    public function testRateLimiterBasicAndBurst(): void
    {
        Config::set('badge.rate_limit_per_minute', 123);
        Config::set('badge.rate_limit_burst_max', 5);
        Config::set('badge.rate_limit_burst_decay_seconds', 15);

        $provider = new AppServiceProvider($this->app);
        $provider->boot(); // registers limiter

        $req = Request::create('/?username=User__Name!!&repository=Repo..Name--123', 'GET');
        $resolver = RateLimiter::limiter('badge');
        $result = $resolver($req);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(2, count($result)); // minute + burst
        /** @var Limit $limit */
        $limit = $result[0];
        $this->assertSame(123, $limit->maxAttempts);
        $this->assertStringContainsString('User__Name', $limit->key);
        $this->assertStringContainsString('Repo..Name--123', $limit->key);
    }

    public function testRateLimiterWithoutBurst(): void
    {
        Config::set('badge.rate_limit_per_minute', 60);
        Config::set('badge.rate_limit_burst_max', 0);
        Config::set('badge.rate_limit_burst_decay_seconds', 0);
        $provider = new AppServiceProvider($this->app);
        $provider->boot();
        $req = Request::create('/?username=abc&repository=repo', 'GET');
        $resolver = RateLimiter::limiter('badge');
        $result = $resolver($req);
        $this->assertCount(1, $result); // only per-minute
    }

    public function testIntFromMixedVariants(): void
    {
        $ref = new \ReflectionClass(AppServiceProvider::class);
        $m = $ref->getMethod('intFromMixed');
        $m->setAccessible(true);
        $this->assertSame(5, $m->invoke(null, 5, 1));
        $this->assertSame(6, $m->invoke(null, '6', 1));
        $this->assertSame(7, $m->invoke(null, 7.9, 1));
        $this->assertSame(9, $m->invoke(null, ['nope'], 9));
    }

    public function testSanitizeKeySegment(): void
    {
        $ref = new \ReflectionClass(AppServiceProvider::class);
        $m = $ref->getMethod('sanitizeKeySegment');
        $m->setAccessible(true);
        $out = $m->invoke(null, 'User Name!*', '/[^A-Za-z0-9_-]/', 'none', 10);
        $this->assertSame('UserName', $out);
        $out2 = $m->invoke(null, null, '/[^A-Za-z0-9_-]/', 'none', 10);
        $this->assertSame('none', $out2);
    }

    public function testBadgeRendererFactoryIsResolvable(): void
    {
        $factory = $this->app->make(BadgeRendererFactory::class);
        $this->assertInstanceOf(BadgeRendererFactory::class, $factory);
    }

    public function testBadgeRendererFactoryIsSingleton(): void
    {
        $factory1 = $this->app->make(BadgeRendererFactory::class);
        $factory2 = $this->app->make(BadgeRendererFactory::class);
        $this->assertSame($factory1, $factory2);
    }

    public function testBadgeRendererFactoryCanCreateStrategies(): void
    {
        $factory = $this->app->make(BadgeRendererFactory::class);
        $strategy = $factory->create('flat');
        $this->assertInstanceOf(\App\Contracts\BadgeRendererStrategyInterface::class, $strategy);
    }
}
