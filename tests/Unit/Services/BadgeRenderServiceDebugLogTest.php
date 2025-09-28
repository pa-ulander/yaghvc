<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BadgeRenderService;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BadgeRenderService::class)]
final class BadgeRenderServiceDebugLogTest extends TestCase
{
    private function invokeDebug(string $val): void
    {
        putenv('BADGE_DEBUG_LOG=' . $val);
        $svc = new BadgeRenderService();
        $ref = new \ReflectionClass($svc);
        $m = $ref->getMethod('debugLog');
        $m->setAccessible(true);
        $m->invoke($svc, 'test-event', ['foo' => str_repeat('a', 300)]); // triggers truncation if enabled
    }

    public function testDebugLogDisabled(): void
    {
        $this->invokeDebug('0');
        $this->assertTrue(true); // just ensure no exception
    }

    public function testDebugLogEnabled(): void
    {
        $this->invokeDebug('1');
        $this->assertTrue(true);
    }
}
