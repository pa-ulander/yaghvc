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
    private const string TOTAL_PATTERN = '/<svg[^>]*width="([0-9.]+)"/i';
    private const string LABEL_FIRST_PATTERN = '/<rect[^>]*fill="#555"[^>]*width="([0-9.]+)"[^>]*>/';
    private const string LABEL_SECOND_PATTERN = '/<rect[^>]*width="([0-9.]+)"[^>]*fill="#555"[^>]*>/';
    private const string STATUS_FIRST_PATTERN = '/<rect[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*>/';
    private const string STATUS_SECOND_PATTERN = '/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/';

    private function invokeEmbed(string $svg, string $dataUri, string $mime = 'png', int $w = 14, int $h = 14): string
    {
        $svc = new BadgeRenderService(new BadgeRendererFactory());
        $ref = new \ReflectionClass($svc);
        $m = $ref->getMethod('embedLogoInSvg');
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

    /**
     * The fallback still accepts alternative rectangle attribute orderings.
     */
    public function testParserFallbackMatchesAlternativeAttributeOrder(): void
    {
        $svg = '<svg width="120" height="20"><rect width="30" fill="#555"/><rect x="30" width="40" fill="#4c1"/></svg>';
        $dataUri = 'data:image/png;base64,' . base64_encode('px');
        $out = $this->invokeEmbed($svg, $dataUri);

        $this->assertStringContainsString('<svg width="144" height="20">', $out);
        $this->assertStringContainsString('<rect width="54" fill="#555"/>', $out);
        $this->assertStringContainsString('<image x="10"', $out);
    }

    /**
     * An error in the first label regex must not read missing captures.
     */
    public function testParserFallbackHandlesFirstLabelRegexError(): void
    {
        $this->assertRegexErrorUsesSimpleFallback(
            '<svg width="120" height="20"><rect fill="#555" width="30"/><rect fill="#4c1" x="30" width="40"/></svg>',
            self::LABEL_FIRST_PATTERN,
            [[self::TOTAL_PATTERN, 1]],
        );
    }

    /**
     * An error in the second label regex must not read missing captures.
     */
    public function testParserFallbackHandlesSecondLabelRegexError(): void
    {
        $this->assertRegexErrorUsesSimpleFallback(
            '<svg width="120" height="20"><rect width="30" fill="#555"/><rect fill="#4c1" x="30" width="40"/></svg>',
            self::LABEL_SECOND_PATTERN,
            [[self::TOTAL_PATTERN, 1], [self::LABEL_FIRST_PATTERN, 0]],
        );
    }

    /**
     * An error in the first status regex must not read missing captures.
     */
    public function testParserFallbackHandlesFirstStatusRegexError(): void
    {
        $this->assertRegexErrorUsesSimpleFallback(
            '<svg width="120" height="20"><rect fill="#555" width="30"/><rect fill="#4c1" x="30" width="40"/></svg>',
            self::STATUS_FIRST_PATTERN,
            [[self::TOTAL_PATTERN, 1], [self::LABEL_FIRST_PATTERN, 1]],
        );
    }

    /**
     * An error in the second status regex must not read missing captures.
     */
    public function testParserFallbackHandlesSecondStatusRegexError(): void
    {
        $this->assertRegexErrorUsesSimpleFallback(
            '<svg width="120" height="20"><rect fill="#555" width="30"/><rect x="30" width="40" fill="#4c1"/></svg>',
            self::STATUS_SECOND_PATTERN,
            [[self::TOTAL_PATTERN, 1], [self::LABEL_FIRST_PATTERN, 1], [self::STATUS_FIRST_PATTERN, 0]],
        );
    }

    /**
     * Verify that a regex backtrack error leaves badge geometry unchanged.
     *
     * @param string $svg Badge whose geometry triggers the fallback
     * @param string $pattern Regex that must exceed the backtrack limit
     * @param list<array{string, int}> $prerequisites Regex results required before the error
     */
    private function assertRegexErrorUsesSimpleFallback(string $svg, string $pattern, array $prerequisites): void
    {
        $dataUri = 'data:image/png;base64,' . base64_encode('px');
        $previousLimit = ini_get('pcre.backtrack_limit');
        $exercised = false;
        $out = '';

        try {
            // Find a limit that fails only the desired regex, with or without PCRE JIT.
            for ($limit = 1; $limit <= 1000; $limit++) {
                ini_set('pcre.backtrack_limit', (string) $limit);
                foreach ($prerequisites as [$prerequisitePattern, $expected]) {
                    if (preg_match($prerequisitePattern, $svg) !== $expected) {
                        continue 2;
                    }
                }
                if (preg_match($pattern, $svg) !== false || preg_last_error() !== PREG_BACKTRACK_LIMIT_ERROR) {
                    continue;
                }

                $exercised = true;
                $out = $this->invokeEmbed($svg, $dataUri);
                break;
            }
        } finally {
            if ($previousLimit !== false) {
                ini_set('pcre.backtrack_limit', $previousLimit);
            }
        }

        $this->assertTrue($exercised, 'Could not reproduce the required PCRE backtrack error.');
        $this->assertStringContainsString('<svg width="120" height="20">', $out);
        $this->assertStringContainsString('<image x="4"', $out);
    }
}
