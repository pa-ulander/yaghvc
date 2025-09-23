<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProfileViews;
use Tests\TestCase;

/**
 * Feature tests for badge rendering behaviors.
 */
class BadgeRenderingTest extends TestCase
{
    public function test_renders_basic_badge_with_default_label(): void
    {
        $this->get('/?username=badgeuser')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('VISITS');
    }

    public function test_applies_label_color_override(): void
    {
        $this->get('/?username=badgeuser&labelColor=red')
            ->assertStatus(200)
            ->assertSee('VISITS')
            ->assertSee('#e05d44');
    }

    public function test_logo_renders_when_label_color_also_set(): void
    {
        $response = $this->get('/?username=badgeuser&logo=github&labelColor=red');
        $response->assertStatus(200)
            ->assertSee('#e05d44')
            ->assertSee('<image', false); // logo embedded
    }

    public function test_embeds_simple_icons_logo_slug(): void
    {
        $response = $this->get('/?username=badgeuser&logo=github');
        // Uncomment for debugging: fwrite(STDERR, $response->getContent());
        $response->assertStatus(200)
            ->assertSee('<image', false); // embedded image element
    }

    public function test_embeds_base64_png_logo(): void
    {
        // 1x1 transparent PNG
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PvSxNwAAAABJRU5ErkJggg==';
        $dataUri = 'data:image/png;base64,' . $pngBase64;
        $response = $this->get('/?username=octocat&logo=' . urlencode($dataUri));
        $response->assertSuccessful();
        $content = $response->getContent();
        $this->assertStringContainsString('<image', $content);
    }

    public function test_embeds_large_base64_png_logo_request_example(): void
    {
        $large = 'iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAACmElEQVQokUWSa0iTcRTGn//26u4b6ZQ0U8lKMqykwPpgZVBEHyLp8jEoIZJADCQ0iCiStIwuZmHRioIuroQss2VkrkIrdeFckiZqdhctTXPOve8Tr7M6X8/zO+fwPEfIwy7IwQA0GgExGYQwyhCmMLRX1z2hJCJSN+xZgqAZnPgCaAUQ0EHICjSYLlKBCDdNQb7HLmeRoy3zQFnzYk/1WTckGUIXCVD+Kw+BpAxtuBXCpkN7bdXt/JL3W3J3xuHg3iTsL/NkNFWVPoWkQOj/wxooCrRhFgiTjI4n9ZVHHQObjxVEY8UGIi1zEhVFCahwdq5qvn+hHkKC0EcBigxwvAnkW3ge7L6TMi+VztOLOOKOY8ulKL68GM2emnjeLF3AZSlz2FCZ6yaHwLGv6pkv8MyxsUoHLcsLwBuHwE0rtdy2UuLWNTpmpkkszQEfnAPDAd47tbaB7NaJR+eXujfmtGTUXgFWp5uwPd8Oi1GBJEmwWYlP34L4PSFw7chPeD+MYnkWUVmy0CeNfe5N8ANIjNWpNmHzqklYrDIGRwRm2gXsM/xofRMOf1AgcbYOAfgxMvgxCmS9+dbh5A6VarxuIMdBDoJ0g+vSreytNpAEux7qqWrK82I+kC2xYOAzyFbz5QNJPrXhdRo4XK/n3WILkxPsbKqwsr8xBB3PjukhGyJJv+qqB+QvkN0mR2Fim5pU1hobzxTYOPbcyJoTNpoAlu6wdZKvIslR0O9VXe0Clc5p2Ge4WDh36ux3ThM/1RqnNhXvilU32cjvINtAf4cKdkzlSHpBTqgNY11JfLtFA+o14NU8Wx/piggNfg2yGVR8EF9/dP37PyCIoDQLs8z9hmv71nsC4wFz9klX2tD4/AEG+gBoQ7KghD8MZ2xdnt7s7wAAAABJRU5ErkJggg==';
        $dataUri = 'data:image/png;base64,' . $large;
        $response = $this->get('/?username=biglogo&label=Visitors%20for%20me&color=orange&style=for-the-badge&abbreviated=true&logo=' . urlencode($dataUri));
        $response->assertSuccessful();
        $this->assertStringContainsString('<image', $response->getContent(), 'Expected large base64 PNG logo to embed');
    }

    public function test_unencoded_data_uri_with_single_space_is_repaired(): void
    {
        // Single space corruption inside base64 should be auto-repaired (space -> '+')
        $corrupted = 'data:image/png;base64,iVBOR w0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $response = $this->get('/?username=badspace&logo=' . $corrupted);
        $response->assertStatus(200);
        $this->assertStringContainsString('<image', $response->getContent());
    }

    public function test_invalid_logo_slug_triggers_validation422(): void
    {
        $this->get('/?username=badgeuser&logo=__not_a_real_icon__')
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_rejects_bad_logo_size_value(): void
    {
        $this->get('/?username=badgeuser&logo=github&logoSize=huge')
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_abbreviates_large_numbers(): void
    {
        ProfileViews::factory()->create([
            'username' => 'abbrseed',
            'visit_count' => 1234,
            'last_visit' => now(),
        ]);
        $this->get('/?username=abbrseed&abbreviated=true')
            ->assertStatus(200)
            ->assertSee('1.2K');
    }

    public function test_adds_base_count_to_stored_value(): void
    {
        $this->get('/?username=baseuser'); // count = 1
        $this->get('/?username=baseuser&base=100')
            ->assertStatus(200)
            ->assertSee('102');
    }

    public function test_invalid_characters_in_username_sanitized(): void
    {
        $this->get('/?username=Bad*Chars!')
            ->assertStatus(200);
    }

    public function test_oversize_raster_logo_rejected(): void
    {
        $oversize = 'data:image/png;base64,' . str_repeat('A', 6000);
        $this->get('/?username=badgelogo&logo=' . urlencode($oversize))
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_embeds_user_reported_png_logo(): void
    {
        // Base64 extracted from user curl (percent-decoding applied).
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAABcVBMVEUAAAAAgM0Af8wolNQAa7YAbbkAQIcAQIYAVJ0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0Af8wAfswAfswAf8wAgM0AgM0AgM0Af80AgM0AgM0AgM0AgM0Af8wAgM0Af80djtIIg84Af8wAfsxYrN4Fg84Gg85RqNwej9MLhM8LhM8AfcsAgM0Hg88AfsshkNNTqd1/v+UXi9AHdsAAYKoAY64ih8kAf81YkcEFV54GV55Sj8EnlNULhc8AecYdebwKcrsAe8gAb7oAXacAXqgAcLwAImUAUpoAVJ0AUpwAUZoAIWMAVJ0AVJ0AUpwAUZwAVJ0AVJ0AVJ0AVJ0AgM0cjtJqteGczetqtOEAf807ndjL5fT9/v7///M5fQ9ntnu9vu12vCi0Oz///6Hw+ebzeufz+x+v+W12e+gz+xqteLu9fmRx+jL3Ovu8/i1zeKrzeUAUpw7e7M8fLQAU50cZ6hqm8WcvNgAVJ3xWY3ZAAAAVnRSTlMAAAAAAAAAAAAREApTvrxRCQQ9rfX0qwErleyUKjncOFv+/v5b/f7+/v7+/v1b/f7+/v7W/7+/v79/v7+/v7+/v7+/jfa2jcBKJHqKAEEO6r0CVC8EFaOox4AAAABYktHRF9z0VEtAAAACXBIWXMAAA7DAAAOwwHHb6hkAAAAB3RJTUUH5QYKDQws/BWF6QAAAONJREFUGNNjYAABRkZOLkZGBhhgZOTm4eXjF4AJMQoKCYuEhYmKCQmCRBjFJSSlwiMiI6PCpaRlxBkZGGXlomNi4+Lj4xISo+XkgQIKikqx8UnJyUnxKcqKKiAB1ajUJDV1Dc00LW0dXSaggF56fLK+gYFhhlGmsQkzRCDL1MzcIhsmYJkTn2tlbWObZ2cP0sKk4OCYH19QWFgQX+TkrMLEwOLiWlySD7I2v7TMzZ2Vgc3D08u7vKKysqLc28vHlx3oVg4//4DAqqrAAH8/DohnODiCgkNCgoM4OOD+5eAIDYVyAZ9mMF8DmkLwAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDIxLTA2LTEwVDE4OjEyOjQ0LTA1OjAwkjvGQgAAACV0RVh0ZGF0ZTptb2RpZnkAMjAyMS0wNi0xMFQxODoxMjo0NC0wNTowMONmfv4AAAAASUVORK5CYII=';
        $dataUri = 'data:image/png;base64,' . $b64;
        $response = $this->get('/?username=userpng&label=Visitors%20for%20me&color=orange&style=for-the-badge&abbreviated=true&logo=' . urlencode($dataUri));
        $response->assertSuccessful();
        $this->assertStringContainsString('<image', $response->getContent(), 'Expected user-reported base64 PNG logo to embed');
    }

    public function test_embeds_percent_encoded_logo_directly(): void
    {
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $raw = 'data:image/png;base64,' . $b64;
        $encoded = rawurlencode($raw);
        $response = $this->get('/?username=encpng&logo=' . $encoded);
        $response->assertSuccessful();
        $this->assertStringContainsString('<image', $response->getContent());
    }

    public function test_applies_logo_color_to_simple_icon(): void
    {
        $response = $this->get('/?username=logocoloruser&logo=github&logoColor=red');
        $response->assertStatus(200)->assertSee('<image', false);
    }

    public function test_logo_color_validation_failure(): void
    {
        // Include an invalid character '!' to break the color/name regex
        $this->get('/?username=logocolorbad&logo=github&logoColor=red!')
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_logo_color_ignored_for_raster(): void
    {
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PvSxNwAAAABJRU5ErkJggg==';
        $dataUri = 'data:image/png;base64,' . $pngBase64;
        $response = $this->get('/?username=logocolorraster&logo=' . urlencode($dataUri) . '&logoColor=blue');
        $response->assertSuccessful();
        $this->assertStringContainsString('<image', $response->getContent());
    }

    public function test_logo_color_auto_simple_icon(): void
    {
        $response = $this->get('/?username=autocolor&logo=github&logoColor=auto');
        $response->assertStatus(200)->assertSee('<image', false);
    }

    public function test_embeds_large_percent_encoded_logo_provided_by_user(): void
    {
        $encoded = 'data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAALGPC%2FxhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAABcVBMVEUAAAAAgM0Af8wolNQAa7YAbbkAQIcAQIYAVJ0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0Af8wAfswAfswAf8wAgM0AgM0AgM0Af80AgM0AgM0AgM0AgM0Af8wAgM0Af80djtIIg84Af8wAfsxYrN4Fg84Gg85RqNwej9MLhM8LhM8AfcsAgM0Hg88AfsshkNNTqd1%2Fv%2BUXi9AHdsAAYKoAY64ih8kAf81YkcEFV54GV55Sj8EnlNULhc8AecYdebwKcrsAe8gAb7oAXacAXqgAcLwAImUAUpoAVJ0AUpwAUZoAIWMAVJ0AVJ0AUpwAUZwAVJ0AVJ0AVJ0AVJ0AgM0cjtJqteGczetqtOEAf807ndjL5fT9%2Fv7%2F%2F%2F%2FM5fQ9ntnu9vu12vCi0Oz%2F%2F%2F6Hw%2Bebzeufz%2Bx%2Bv%2BW12e%2Bgz%2BxqteLu9fmRx%2BjL3Ovu8%2Fi1zeKrzeUAUpw7e7M8fLQAU50cZ6hqm8WcvNgAVJ3xWY3ZAAAAVnRSTlMAAAAAAAAAAAAREApTvrxRCQQ9rfX0qwErleyUKjncOFv%2B%2Fv5b%2Ff7%2B%2Fv7%2B%2Fv1b%2Ff7%2B%2Fv7%2BW%2F7%2B%2Fv79%2Fv7%2B%2Fv7%2B%2Fv7%2B%2Fjfa2jcBKJHqKAEEO6r0CVC8EFaOox4AAAABYktHRF9z0VEtAAAACXBIWXMAAA7DAAAOwwHHb6hkAAAAB3RJTUUH5QYKDQws%2FBWF6QAAAONJREFUGNNjYAABRkZOLkZGBhhgZOTm4eXjF4AJMQoKCYuEhYmKCQmCRBjFJSSlwiMiI6PCpaRlxBkZGGXlomNi4%2BLj4xISo%2BXkgQIKikqx8UnJyUnxKcqKKiAB1ajUJDV1Dc00LW0dXSaggF56fLK%2BgYFhhlGmsQkzRCDL1MzcIhsmYJkTn2tlbWObZ2cP0sKk4OCYH19QWFgQX%2BTkrMLEwOLiWlySD7I2v7TMzZ2Vgc3D08u7vKKysqLc28vHlx3oVg4%2F%2F4DAqqrAAH8%2FDohnODiCgkNCgoM4OOD%2B5eAIDYVyAZ9mMF8DmkLwAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDIxLTA2LTEwVDE4OjEyOjQ0LTA1OjAwkjvGQgAAACV0RVh0ZGF0ZTptb2RpZnkAMjAyMS0wNi0xMFQxODoxMjo0NC0wNTowMONmfv4AAAAASUVORK5CYII%3D';
        $response = $this->get('/?username=bigencpng&label=Visitors%20for%20me&color=orange&style=for-the-badge&abbreviated=true&logo=' . $encoded);
        $response->assertSuccessful();
        $this->assertStringContainsString('<image', $response->getContent(), 'Expected percent-encoded large PNG logo to embed');
    }

    public function test_embedded_data_uri_has_no_spaces(): void
    {
        $b64WithSpaces = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADkAAAA5CAYAAACMGIOF AAAACXBIWXMAAA7EAAAOxAGVKw4b';
        $response = $this->get('/?username=spacetest&logo=' . $b64WithSpaces);
        $response->assertStatus(200);
        $svg = $response->getContent();
        $this->assertStringContainsString('<image', $svg);
        // Extract data uri
        if (preg_match('/<image[^>]*href="(data:image\/png;base64,[^"]+)"/i', $svg, $m)) {
            $this->assertStringNotContainsString(' ', $m[1], 'Embedded data URI still contains spaces');
        } else {
            $this->fail('Did not find embedded image element with data URI');
        }
    }

    public function test_data_uri_logo_expands_label_geometry(): void
    {
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PvSxNwAAAABJRU5ErkJggg==';
        $dataUri = 'data:image/png;base64,' . $pngBase64;
        $response = $this->get('/?username=geomuser&logo=' . urlencode($dataUri));
        $response->assertStatus(200);
        $svg = $response->getContent();
        // Capture original (without logo) for comparison
        $noLogo = $this->get('/?username=geomuser2')->getContent();
        // Extract label rect widths
        // Height may vary (e.g., 20, 28) depending on style; match width + fill only.
        $rectPattern = '/<rect[^>]*width="([0-9.]+)"[^>]*fill="#555"[^>]*>/';
        preg_match($rectPattern, $noLogo, $mNo);
        preg_match($rectPattern, $svg, $mWith);
        $this->assertNotEmpty($mNo, 'Missing label rect in baseline badge');
        $this->assertNotEmpty($mWith, 'Missing label rect in logo badge');
        $baseWidth = (float)$mNo[1];
        $logoWidth = (float)$mWith[1];
        $this->assertGreaterThan($baseWidth, $logoWidth, 'Label width not expanded when logo present');
    }

    public function test_total_svg_width_increases_with_data_uri_logo(): void
    {
        $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PvSxNwAAAABJRU5ErkJggg==';
        $dataUri = 'data:image/png;base64,' . $pngBase64;
        $baseline = $this->get('/?username=geomwide')->getContent();
        $withLogo = $this->get('/?username=geomwide2&logo=' . urlencode($dataUri))->getContent();
        preg_match('/<svg[^>]*width="([0-9.]+)"/i', $baseline, $b);
        preg_match('/<svg[^>]*width="([0-9.]+)"/i', $withLogo, $w);
        $this->assertNotEmpty($b, 'Missing width in baseline svg');
        $this->assertNotEmpty($w, 'Missing width in logo svg');
        $base = (float)$b[1];
        $logo = (float)$w[1];
        $this->assertGreaterThan($base, $logo, 'SVG total width not increased when logo added');
    }
}
