<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class LogoUnencodedDataUriEdgeTest extends TestCase
{
    // Direct (unencoded) data URI taken from user report (short 16x16 png badge example)
    private const DATA_URI = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAABcVBMVEUAAAAAgM0Af8wolNQAa7YAbbkAQIcAQIYAVJ0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0AgM0Af8wAfswAfswAf8wAgM0AgM0AgM0Af80AgM0AgM0AgM0AgM0Af8wAgM0Af80djtIIg84Af8wAfsxYrN4Fg84Gg85RqNwej9MLhM8LhM8AfcsAgM0Hg88AfsshkNNTqd1/v+UXi9AHdsAAYKoAY64ih8kAf81YkcEFV54GV55Sj8EnlNULhc8AecYdebwKcrsAe8gAb7oAXacAXqgAcLwAImUAUpoAVJ0AUpwAUZoAIWMAVJ0AVJ0AUpwAUZwAVJ0AVJ0AVJ0AVJ0AgM0cjtJqteGczetqtOEAf807ndjL5fT9/v7////M5fQ9ntnu9vu12vCi0Oz///6Hw+ebzeufz+x+v+W12e+gz+xqteLu9fmRx+jL3Ovu8/i1zeKrzeUAUpw7e7M8fLQAU50cZ6hqm8WcvNgAVJ3xWY3ZAAAAVnRSTlMAAAAAAAAAAAAREApTvrxRCQQ9rfX0qwErleyUKjncOFv+/v5b/f7+/v7+/v1b/f7+/v7+W/7+/v79/v7+/v7+/v7+/jfa2jcBKJHqKAEEO6r0CVC8EFaOox4AAAABYktHRF9z0VEtAAAACXBIWXMAAA7DAAAOwwHHb6hkAAAAB3RJTUUH5QYKDQws/BWF6QAAAONJREFUGNNjYAABRkZOLkZGBhhgZOTm4eXjF4AJMQoKCYuEhYmKCQmCRBjFJSSlwiMiI6PCpaRlxBkZGGXlomNi4+Lj4xISo+XkgQIKikqx8UnJyUnxKcqKKiAB1ajUJDV1Dc00LW0dXSaggF56fLK+gYFhhlGmsQkzRCDL1MzcIhsmYJkTn2tlbWObZ2cP0sKk4OCYH19QWFgQX+TkrMLEwOLiWlySD7I2v7TMzZ2Vgc3D08u7vKKysqLc28vHlx3oVg4//4DAqqrAAH8/DohnODiCgkNCgoM4OOD+5eAIDYVyAZ9mMF8DmkLwAAAAJXRFWHRkYXRlOmNyZWF0ZQAyMDIxLTA2LTEwVDE4OjEyOjQ0LTA1OjAwkjvGQgAAACV0RVh0ZGF0ZTptb2RpZnkAMjAyMS0wNi0xMFQxODoxMjo0NC0wNTowMONmfv4AAAAASUVORK5CYII=';

    public function test_unencoded_data_uri_embeds_logo(): void
    {
        $url = '/?username=edge-unencoded&label=Congratulations!%20You%20are%20visitor%20number&color=orange&style=for-the-badge&abbreviated=true&logo=' . self::DATA_URI;
        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<image', $response->getContent(), 'Expected <image> element for unencoded data URI');
    }
}
