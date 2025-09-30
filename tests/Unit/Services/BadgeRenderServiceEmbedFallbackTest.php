<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BadgeRenderService;
use App\Factories\BadgeRendererFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BadgeRenderService::class)]
final class BadgeRenderServiceEmbedFallbackTest extends TestCase
{
    private function invokeEmbed(string $svg, string $dataUri, string $mime = 'png', int $w = 14, int $h = 14): string
    {
        $svc = new BadgeRenderService(new BadgeRendererFactory());
        $ref = new \ReflectionClass($svc);
        $m = $ref->getMethod('embedLogoInSvg');
        $m->setAccessible(true);
        /** @var string $out */
        $out = $m->invoke($svc, $svg, $dataUri, $mime, $w, $h);
        return $out;
    }

    public function testParserFallbackMissingTotalWidth(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="70" height="20"><rect fill="#555" width="30"/><rect fill="#4c1" x="30" width="40"/></svg>';
        $svgNoWidth = preg_replace('/ width="70"/', '', $svg); // remove width attr
        $dataUri = 'data:image/png;base64,' . base64_encode('px');
        $out = $this->invokeEmbed($svgNoWidth, $dataUri);
        $this->assertStringContainsString('<image', $out);
    }

    public function testParserFallbackMissingLabelRect(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="70" height="20"><rect fill="#4c1" x="30" width="40"/><rect fill="#4c1" width="40" x="30"/></svg>';
        // no rect with fill #555
        $dataUri = 'data:image/png;base64,' . base64_encode('px');
        $out = $this->invokeEmbed($svg, $dataUri);
        $this->assertStringContainsString('<image', $out);
    }

    public function testParserFallbackMissingStatusRect(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="70" height="20"><rect fill="#555" width="30"/></svg>';
        $dataUri = 'data:image/png;base64,' . base64_encode('px');
        $out = $this->invokeEmbed($svg, $dataUri);
        $this->assertStringContainsString('<image', $out);
    }

    public function testParserFallbackWidthDeltaLarge(): void
    {
        // combined label+status width (30+40) != total width 120 -> triggers width delta logging path
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20"><rect fill="#555" width="30"/><rect fill="#4c1" x="30" width="40"/></svg>';
        $dataUri = 'data:image/png;base64,' . base64_encode('px');
        $out = $this->invokeEmbed($svg, $dataUri);
        $this->assertStringContainsString('<image', $out);
    }
}
