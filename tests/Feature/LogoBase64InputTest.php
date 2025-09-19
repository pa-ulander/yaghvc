<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class LogoBase64InputTest extends TestCase
{
    private string $ua = 'TestSuite/1.0';

    public function test_embeds_raw_base64_png_logo(): void
    {
        $raw = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $response = $this->withHeaders(['User-Agent' => $this->ua])->get('/?username=test-user&logo=' . $raw);
        $response->assertOk();
        $svg = $response->getContent();
        $this->assertIsString($svg);
        $this->assertStringContainsString('<image', $svg);
        $this->assertStringContainsString('data:image/png;base64', $svg);
    }

    public function test_embeds_urlencoded_base64_png_logo(): void
    {
        $raw = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $encoded = rawurlencode($raw);
        $response = $this->withHeaders(['User-Agent' => $this->ua])->get('/?username=test-user&logo=' . $encoded);
        $response->assertOk();
        $svg = $response->getContent();
        $this->assertIsString($svg);
        $this->assertStringContainsString('<image', $svg);
        $this->assertStringContainsString('data:image/png;base64', $svg);
    }

    public function test_rejects_invalid_base64_logo(): void
    {
        $bad = 'not_base64%%%%';
        $response = $this->withHeaders(['User-Agent' => $this->ua])->get('/?username=test-user&logo=' . $bad);
        $response->assertStatus(422);
    }
}
