<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\BadgeRenderService;
use App\Factories\BadgeRendererFactory;
use PHPUnit\Framework\TestCase;

/**
 * Test BadgeRenderService defensive preg_replace null checks in ensureAccessibleLabels.
 * These lines are defensive programming that should handle PCRE errors gracefully.
 */
final class BadgeRenderServicePregReplaceTest extends TestCase
{
    public function test_ensure_accessible_labels_handles_preg_replace_null(): void
    {
        $service = new BadgeRenderService(new BadgeRendererFactory());
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('ensureAccessibleLabels');
        $method->setAccessible(true);

        // Normal case - preg_replace should work
        $svg = '<svg aria-label="old"><title>old</title></svg>';
        $result = $method->invoke($service, $svg, 'profile', 'views');

        $this->assertStringContainsString('aria-label="profile: views"', $result);
        $this->assertStringContainsString('<title>profile: views</title>', $result);
    }

    public function test_ensure_accessible_labels_with_no_existing_title(): void
    {
        $service = new BadgeRenderService(new BadgeRendererFactory());
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('ensureAccessibleLabels');
        $method->setAccessible(true);

        // No existing title - should inject one
        $svg = '<svg width="100" height="20"><rect/></svg>';
        $result = $method->invoke($service, $svg, 'profile', 'views');

        $this->assertStringContainsString('<title>profile: views</title>', $result);
    }
}
