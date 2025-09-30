<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BadgeRenderService;
use App\Factories\BadgeRendererFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BadgeRenderService::class)]
final class BadgeRenderServiceCanonicalizeTest extends TestCase
{
    private function invokeCanonicalize(string $uri): string
    {
        $svc = new BadgeRenderService(new BadgeRendererFactory());
        $ref = new \ReflectionClass($svc);
        $m = $ref->getMethod('canonicalizeDataUri');
        $m->setAccessible(true);
        /** @var string $out */
        $out = $m->invoke($svc, $uri);
        return $out;
    }

    public function testEarlyReturnNonDataUri(): void
    {
        $input = 'not-a-data-uri';
        $this->assertSame($input, $this->invokeCanonicalize($input));
    }

    public function testEmptySanitizedPayloadReturnsOriginal(): void
    {
        $input = 'data:image/png;base64,'; // empty payload
        $this->assertSame($input, $this->invokeCanonicalize($input));
    }

    public function testInvalidAlphabetMutatedLogsAndReturnsSanitized(): void
    {
        // Introduce space + invalid character '*', expect it to pass through (still invalid) but sanitized removed space.
        $raw = 'iVBOR w0KGgo*';
        $input = 'data:image/png;base64,' . $raw;
        $out = $this->invokeCanonicalize($input);
        $this->assertStringStartsWith('data:image/png;base64,', $out);
        // space removed -> plus replaced; invalid * remains
        $this->assertStringContainsString('*', $out);
        $this->assertFalse(str_contains($out, ' '));
    }

    public function testDecodeFailedMutatedReturnsSanitized(): void
    {
        // Craft payload with whitespace removed (mutation) that still is valid alphabet but produces decode failure in strict mode by including an invalid padding scenario '*'
        $raw = 'QUJDRA*';
        $input = 'data:image/png;base64,' . $raw;
        $out = $this->invokeCanonicalize($input);
        $this->assertSame('data:image/png;base64,' . str_replace(' ', '+', 'QUJDRA*'), $out);
    }

    public function testReencodedBranch(): void
    {
        $payload = base64_encode('PNGDATA');
        $whitespaceInjected = substr($payload, 0, 3) . "  \n\t" . substr($payload, 3);
        $input = 'data:image/png;base64,' . $whitespaceInjected;
        $out = $this->invokeCanonicalize($input);
        // Reproduce canonicalization logic locally for expectation
        $sanitized = str_replace(' ', '+', $whitespaceInjected);
        $sanitized = preg_replace('/\s+/', '', $sanitized) ?? $sanitized;
        $bin = base64_decode($sanitized, true);
        $expected = $bin === false || $bin === '' ? $sanitized : base64_encode($bin);
        $this->assertSame('data:image/png;base64,' . $expected, $out);
    }
}
