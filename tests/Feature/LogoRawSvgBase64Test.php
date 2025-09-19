<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class LogoRawSvgBase64Test extends TestCase
{
    private string $ua = 'TestSuite/1.0';

    public function test_embeds_raw_svg_base64_logo(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"><rect width="12" height="12" fill="red"/></svg>';
        $b64 = base64_encode($svg);
        $response = $this->withHeaders(['User-Agent' => $this->ua])->get('/?username=test-user&logo=' . $b64 . '&logoSize=auto');
        $response->assertOk();
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('<image', $content);
        $this->assertStringContainsString('data:image/svg+xml;base64', $content);
    }

    public function test_rejects_unsafe_svg_with_script(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $b64 = base64_encode($svg);
        $response = $this->withHeaders(['User-Agent' => $this->ua])->get('/?username=test-user&logo=' . $b64);
        $response->assertStatus(422);
    }
}
