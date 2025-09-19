<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class LogoAmbiguousBase64Test extends TestCase
{
    private string $ua = 'TestSuite/1.0';

    public function test_ambiguous_base64_rejected(): void
    {
        // Random base64 that does not correspond to allowed image signatures
        $binary = random_bytes(32);
        $b64 = base64_encode($binary);
        $response = $this->withHeaders(['User-Agent' => $this->ua])->get('/?username=test-user&logo=' . $b64);
        $response->assertStatus(422);
    }
}
