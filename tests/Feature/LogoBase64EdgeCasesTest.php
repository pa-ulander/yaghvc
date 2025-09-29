<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Feature tests exercising raw base64 logo edge cases in ProfileViewsRequest::passedValidation()
 * These cover lines 199 (unsupported MIME), 205 (oversize), 210 (unsafe SVG)
 */
class LogoBase64EdgeCasesTest extends TestCase
{
    private string $ua = 'TestSuite/1.0';

    public function test_rejects_raw_base64_with_unsupported_mime(): void
    {
        // Deterministic bytes that won't match any supported image signature (PNG/JPEG/GIF/SVG)
        $binary = 'PLAINTEXT_NOT_AN_IMAGE_FORMAT';
        $logo = base64_encode($binary);

        $response = $this->withHeaders(['User-Agent' => $this->ua])
            ->get('/?username=test-user&logo=' . $logo);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Validation errors',
            'data' => ['logo' => ['Unsupported or ambiguous logo format.']]
        ]);
    }

    public function test_rejects_raw_base64_logo_exceeding_max_bytes(): void
    {
        // Set a very small max_bytes limit to trigger oversize rejection
        Config::set('badge.logo_max_bytes', 50);

        // Valid PNG but over 50 bytes when decoded
        // This is a 1x1 transparent PNG (~70 bytes decoded)
        $logo = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

        $response = $this->withHeaders(['User-Agent' => $this->ua])
            ->get('/?username=test-user&logo=' . $logo);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Validation errors',
            'data' => ['logo' => ['Logo image exceeds maximum allowed size.']]
        ]);
    }

    public function test_rejects_raw_base64_unsafe_svg(): void
    {
        // SVG containing <script> tag which should be rejected by sanitization
        $unsafeSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script><rect width="10" height="10"/></svg>';
        $logo = base64_encode($unsafeSvg);

        $response = $this->withHeaders(['User-Agent' => $this->ua])
            ->get('/?username=test-user&logo=' . $logo);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Validation errors',
            'data' => ['logo' => ['Unsafe SVG content rejected.']]
        ]);
    }

    public function test_rejects_raw_base64_svg_with_javascript_href(): void
    {
        // SVG with javascript: href which should be rejected
        $unsafeSvg = '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><rect width="10" height="10"/></a></svg>';
        $logo = base64_encode($unsafeSvg);

        $response = $this->withHeaders(['User-Agent' => $this->ua])
            ->get('/?username=test-user&logo=' . $logo);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Validation errors',
            'data' => ['logo' => ['Unsafe SVG content rejected.']]
        ]);
    }
}
