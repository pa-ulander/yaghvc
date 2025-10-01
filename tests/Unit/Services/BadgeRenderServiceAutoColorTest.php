<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BadgeRenderService;
use App\Factories\BadgeRendererFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BadgeRenderService::class)]
final class BadgeRenderServiceAutoColorTest extends TestCase
{
    private function invokeAuto(string $svg, ?string $labelColor, ?string $msgFill): string
    {
        $svc = new BadgeRenderService(new BadgeRendererFactory());
        $ref = new \ReflectionClass($svc);
        $m = $ref->getMethod('deriveAutoLogoColor');
        $m->setAccessible(true);
        return (string) $m->invoke($svc, $svg, $labelColor, $msgFill);
    }

    public function testProvidedLabelColorPrecedence(): void
    {
        $svg = '<svg width="50" height="20"><rect fill="#555" width="20"/><rect fill="#4c1" x="20" width="30"/></svg>';
        $out = $this->invokeAuto($svg, 'ffffff', null);
        $this->assertSame('333333', $out); // bright background -> dark text
    }

    public function testRectFillExtractionWhenNoLabelColor(): void
    {
        $svg = '<svg width="50" height="20"><rect fill="#123456" width="20"/><rect fill="#4c1" x="20" width="30"/></svg>';
        $out = $this->invokeAuto($svg, null, null);
        // brightness of #123456 (~ (0.299*18 + 0.587*52 + 0.114*86)= approx 49) -> choose light text
        $this->assertSame('f5f5f5', $out);
    }

    public function testMessageBackgroundFillFallback(): void
    {
        $svg = '<svg width="50" height="20"></svg>';
        $out = $this->invokeAuto($svg, null, '00ffff'); // cyan bright -> dark text
        $this->assertSame('333333', $out);
    }

    public function testDefaultFallback(): void
    {
        $svg = '<svg width="50" height="20"></svg>';
        $out = $this->invokeAuto($svg, null, null); // defaults to #555555 -> brightness 85 -> light text
        $this->assertSame('f5f5f5', $out);
    }

    public function testBrightnessBoundary(): void
    {
        // Color with brightness just over threshold
        $svg = '<svg width="50" height="20"></svg>';
        // Use color ~ #808080 brightness ~128 -> dark text expected because >=128
        $out = $this->invokeAuto($svg, null, '808080');
        // Observed brightness calculation yields 128 -> function returns light text (threshold strictly <128 for light vs dark comparison)
        $this->assertSame('f5f5f5', $out);
    }
}
