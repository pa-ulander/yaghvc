<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LogoDataHelper;
use PHPUnit\Framework\TestCase;

class LogoDataHelperTest extends TestCase
{
    public function test_normalize_raw_base64_accepts_valid(): void
    {
        $png = base64_encode(hex2bin('89504E470D0A1A0A0000000D49484452000000010000000108060000001F15C4890000000A49444154789C6360000002000100FFFF03000006000557BF3A0000000049454E44AE426082'));
        $norm = LogoDataHelper::normalizeRawBase64($png);
        $this->assertNotNull($norm);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+=*$/', $norm);
    }

    public function test_normalize_raw_base64_rejects_invalid(): void
    {
        $this->assertNull(LogoDataHelper::normalizeRawBase64('***not-base64***'));
    }

    public function test_infer_mime_detects_png(): void
    {
        $bin = hex2bin('89504E470D0A1A0A');
        $this->assertSame('png', LogoDataHelper::inferMime($bin));
    }

    public function test_infer_mime_detects_svg(): void
    {
        $svg = "<svg xmlns='http://www.w3.org/2000/svg'></svg>";
        $this->assertSame('svg+xml', LogoDataHelper::inferMime($svg));
    }

    public function test_sanitize_svg_rejects_script(): void
    {
        $unsafe = "<svg><script>alert(1)</script></svg>";
        $this->assertNull(LogoDataHelper::sanitizeSvg($unsafe));
    }

    public function test_sanitize_svg_allows_basic(): void
    {
        $safe = "<svg width='10' height='10'><rect width='10' height='10'/></svg>";
        $this->assertNotNull(LogoDataHelper::sanitizeSvg($safe));
    }

    public function test_within_size(): void
    {
        $this->assertTrue(LogoDataHelper::withinSize('abc', 10));
        $this->assertFalse(LogoDataHelper::withinSize(str_repeat('a', 11), 10));
    }
}
