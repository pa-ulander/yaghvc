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
    public function testRendersBasicBadgeWithDefaultLabel(): void
    {
        $this->get('/?username=badgeuser')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertSee('VISITS');
    }

    public function testAppliesLabelColorOverride(): void
    {
        $this->get('/?username=badgeuser&labelColor=red')
            ->assertStatus(200)
            ->assertSee('VISITS')
            ->assertSee('#e05d44');
    }

    public function testLogoRendersWhenLabelColorAlsoSet(): void
    {
        $response = $this->get('/?username=badgeuser&logo=github&labelColor=red');
        $response->assertStatus(200)
            ->assertSee('#e05d44')
            ->assertSee('<image', false); // logo embedded
    }

    public function testEmbedsSimpleIconsLogoSlug(): void
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

    public function test_rejects_unencoded_data_uri_with_space(): void
    {
        // Insert a raw space (should be %20 if encoded) to simulate user error
        $bad = 'data:image/png;base64,iVBOR w0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $this->get('/?username=badspace&logo=' . $bad)
            ->assertStatus(422)
            ->assertJsonPath('data.logo.0', fn($msg) => str_contains($msg, 'percent-encode'));
    }

    public function testInvalidLogoSlugTriggersValidation422(): void
    {
        $this->get('/?username=badgeuser&logo=__not_a_real_icon__')
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function testRejectsBadLogoSizeValue(): void
    {
        $this->get('/?username=badgeuser&logo=github&logoSize=huge')
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function testAbbreviatesLargeNumbers(): void
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

    public function testAddsBaseCountToStoredValue(): void
    {
        $this->get('/?username=baseuser'); // count = 1
        $this->get('/?username=baseuser&base=100')
            ->assertStatus(200)
            ->assertSee('102');
    }

    public function testInvalidCharactersInUsernameSanitized(): void
    {
        $this->get('/?username=Bad*Chars!')
            ->assertStatus(200);
    }

    public function testOversizeRasterLogoRejected(): void
    {
        $oversize = 'data:image/png;base64,' . str_repeat('A', 6000);
        $this->get('/?username=badgelogo&logo=' . urlencode($oversize))
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function testEmbedsUserReportedPngLogo(): void
    {
        // Base64 extracted from user curl (percent-decoding applied).
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAABcVBMVEUAAAAAgM0Af8wolNQAa7YAbbkAQIcAQIYAVJ0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0Af8wAfswAfswAf8wAgM0AgM0AgM0Af80AgM0AgM0AgM0AgM0Af8wAgM0Af80djtIIg84Af8wAfsxYrN4Fg84Gg85RqNwej9MLhM8LhM8AfcsAgM0Hg88AfsshkNNTqd1/v+UXi9AHdsAAYKoAY64ih8kAf81YkcEFV54GV55Sj8EnlNULhc8AecYdebwKcrsAe8gAb7oAXacAXqgAcLwAImUAUpoAVJ0AUpwAUZoAIWMAVJ0AVJ0AUpwAUZwAVJ0AVJ0AVJ0AVJ0AgM0cjtJqteGczetqtOEAf807ndjL5fT9/v7///M5fQ9ntnu9vu12vCi0Oz///6Hw+ebzeufz+x+v+W12e+gz+xqteLu9fmRx+jL3Ovu8/i1zeKrzeUAUpw7e7M8fLQAU50cZ6hqm8WcvNgAVJ3xWY3ZAAAAVnRSTlMAAAAAAAAAAAAREApTvrxRCQQ9rfX0qwErleyUKjncOFv+/v5b/f7+/v7+/v1b/f7+/v7W/7+/v79/v7+/v7+/v7+/jfa2jcBKJHqKAEEO6r0CVC8EFaOox4AAAABYktHRF9z0VEtAAAACXBIWXMAAA7DAAAOwwHHb6hkAAAAB3RJTUUH5QYKDQws/BWF6QAAAONJREFUGNNjYAABRkZOLkZGBhhgZOTm4eXjF4AJMQoKCYuEhYmKCQmCRBjFJSSlwiMiI6PCpaRlxBkZGGXlomNi4+Lj4xISo+XkgQIKikqx8UnJyUnxKcqKKiAB1ajUJDV1Dc00LW0dXSaggF56fLK+gYFhhlGmsQkzRCDL1MzcIhsmYJkTn2tlbWObZ2cP0sKk4OCYH19QWFgQX+TkrMLEwOLiWlySD7I2v7TMzZ2Vgc3D08u7vKKysqLc28vHlx3oVg4//4DAqqrAAH8/DohnODiCgkNCgoM4OOD+5eAIDYVyAZ9mMF8DmkLwAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDIxLTA2LTEwVDE4OjEyOjQ0LTA1OjAwkjvGQgAAACV0RVh0ZGF0ZTptb2RpZnkAMjAyMS0wNi0xMFQxODoxMjo0NC0wNTowMONmfv4AAAAASUVORK5CYII=';
        $dataUri = 'data:image/png;base64,' . $b64;
        $response = $this->get('/?username=userpng&label=Visitors%20for%20me&color=orange&style=for-the-badge&abbreviated=true&logo=' . urlencode($dataUri));
        $response->assertSuccessful();
        $this->assertStringContainsString('<image', $response->getContent(), 'Expected user-reported base64 PNG logo to embed');
    }

    public function testEmbedsPercentEncodedLogoDirectly(): void
    {
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
        $raw = 'data:image/png;base64,' . $b64;
        $encoded = rawurlencode($raw);
        $response = $this->get('/?username=encpng&logo=' . $encoded);
        $response->assertSuccessful();
        $this->assertStringContainsString('<image', $response->getContent());
    }

    public function testEmbedsLargePercentEncodedLogoProvidedByUser(): void
    {
        $encoded = 'data%3Aimage%2Fpng%3Bbase64%2CiVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAALGPC%2FxhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAABcVBMVEUAAAAAgM0Af8wolNQAa7YAbbkAQIcAQIYAVJ0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0Af8wAfswAfswAf8wAgM0AgM0AgM0Af80AgM0AgM0AgM0AgM0Af8wAgM0Af80djtIIg84Af8wAfsxYrN4Fg84Gg85RqNwej9MLhM8LhM8AfcsAgM0Hg88AfsshkNNTqd1%2Fv%2BUXi9AHdsAAYKoAY64ih8kAf81YkcEFV54GV55Sj8EnlNULhc8AecYdebwKcrsAe8gAb7oAXacAXqgAcLwAImUAUpoAVJ0AUpwAUZoAIWMAVJ0AVJ0AUpwAUZwAVJ0AVJ0AVJ0AVJ0AgM0cjtJqteGczetqtOEAf807ndjL5fT9%2Fv7%2F%2F%2F%2FM5fQ9ntnu9vu12vCi0Oz%2F%2F%2F6Hw%2Bebzeufz%2Bx%2Bv%2BW12e%2Bgz%2BxqteLu9fmRx%2BjL3Ovu8%2Fi1zeKrzeUAUpw7e7M8fLQAU50cZ6hqm8WcvNgAVJ3xWY3ZAAAAVnRSTlMAAAAAAAAAAAAREApTvrxRCQQ9rfX0qwErleyUKjncOFv%2B%2Fv5b%2Ff7%2B%2Fv7%2B%2Fv1b%2Ff7%2B%2Fv7%2BW%2F7%2B%2Fv79%2Fv7%2B%2Fv7%2B%2Fv7%2B%2Fjfa2jcBKJHqKAEEO6r0CVC8EFaOox4AAAABYktHRF9z0VEtAAAACXBIWXMAAA7DAAAOwwHHb6hkAAAAB3RJTUUH5QYKDQws%2FBWF6QAAAONJREFUGNNjYAABRkZOLkZGBhhgZOTm4eXjF4AJMQoKCYuEhYmKCQmCRBjFJSSlwiMiI6PCpaRlxBkZGGXlomNi4%2BLj4xISo%2BXkgQIKikqx8UnJyUnxKcqKKiAB1ajUJDV1Dc00LW0dXSaggF56fLK%2BgYFhhlGmsQkzRCDL1MzcIhsmYJkTn2tlbWObZ2cP0sKk4OCYH19QWFgQX%2BTkrMLEwOLiWlySD7I2v7TMzZ2Vgc3D08u7vKKysqLc28vHlx3oVg4%2F%2F4DAqqrAAH8%2FDohnODiCgkNCgoM4OOD%2B5eAIDYVyAZ9mMF8DmkLwAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDIxLTA2LTEwVDE4OjEyOjQ0LTA1OjAwkjvGQgAAACV0RVh0ZGF0ZTptb2RpZnkAMjAyMS0wNi0xMFQxODoxMjo0NC0wNTowMONmfv4AAAAASUVORK5CYII%3D';
        $response = $this->get('/?username=bigencpng&label=Visitors%20for%20me&color=orange&style=for-the-badge&abbreviated=true&logo=' . $encoded);
        $response->assertSuccessful();
        $this->assertStringContainsString('<image', $response->getContent(), 'Expected percent-encoded large PNG logo to embed');
    }
}
