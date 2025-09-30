<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BadgeRenderService;
use App\Factories\BadgeRendererFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BadgeRenderService::class)]
final class BadgeRenderServiceRecolorTest extends TestCase
{
    private function invokeRecolor(string $fragment, string $hex): ?string
    {
        $svc = new BadgeRenderService(new BadgeRendererFactory());
        $ref = new \ReflectionClass($svc);
        $m = $ref->getMethod('recolorSvg');
        $m->setAccessible(true);
        /** @var ?string $out */
        $out = $m->invoke($svc, $fragment, $hex);
        return $out;
    }

    public function testCurrentColorPathAddsFillAndReplaces(): void
    {
        $svg = '<svg viewBox="0 0 10 10"><path d="M0 0h10v10H0z" fill="currentColor"/></svg>';
        $out = $this->invokeRecolor($svg, 'ff0000');
        $this->assertNotNull($out);
        $this->assertStringContainsString('#ff0000', $out);
    }

    public function testPathInjectionWhenNoFillAttributes(): void
    {
        $svg = '<svg viewBox="0 0 10 10"><path d="M0 0h10v10H0z"/></svg>';
        $out = $this->invokeRecolor($svg, '00ff00');
        $this->assertNotNull($out);
        $this->assertStringContainsString('#00ff00', $out);
    }

    public function testNonSvgReturnsNull(): void
    {
        // Fragment without <svg> tag should early-return null
        $fragment = '<path d="M0 0h10v10H0z" fill="#123456"/>';
        $out = $this->invokeRecolor($fragment, '123456');
        $this->assertNull($out);
    }
}
