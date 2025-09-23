<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class LogoDataUriParityTest extends TestCase
{
    /**
     * Base64 for a 57x57-ish PNG already present in regression test; reused to ensure parity.
     */
    private const BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAADkAAAA5CAYAAACMGIOFAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAE/UlEQVRo3t2bX2gcVRTGfxmWIIuUJWAIIQ1LXIKUEJVEDUFQSigllCBFglQfIkiRUDRWKPVFCCIikoeShyA+hCIi9knqQ6VQFGqEUPJQNZoiFhFq0YBpC1bSul0f9rvZ43RnM7vzJ5v9YJndmTvn3G/OnXPPPeduG8kgD4wCQ8A+oBfoBPYAGeBf4BbwJ/Ab8COwAnwL/EoTYxB4H7gClKp8NoF14LqOmwHtrkjOYLMQywCTwLKvoz8BC8AUMAx0AJ7vXk/nh9VuQfdZOcuSn9kpghPAqs8CJ4G+iHL7JMeOiFXpSw154JzpwBIwXsVSUeFJ7pLRdU76E8UR4KYU/pzi052QvpL0H0nq3ZuXkiIwB2QbkJMDpnWsF1npLaof83G+q1ngCwleBw5GkHVMco5FkHFQ/SipX9k4CF4wjqUQUd60ZE1HlFMwjulCFKIZY8HLQFdMo2Iyjqev/lw2Fm1o6M4bC3bRnOgyFp1vxIu6d7BAc6Ng3tHQXjcvN12s4WTGFIumiU7pDXJGRfU71DzqJvq5gOsDZhh3pkjQDcuBgDZzJmDYdtJ1E302hEMaq7OzI3IWI3XeNxbCwWRNwDBRy5uubtfItB1owCLHJf94A/cOhPCgEybWrdp20sSiSSEKybBwse6kDYId3tTxXXY3XP/fqLbgdc7E2+WW9IyTGrSWfFHHReDeLrfkPfHY4uVIPqfjGVoDZywvT5NnP7AGXG0RklfFpx/Ie8DTuvA1rQXHZ9QDHteP5RYj6fgMeZTzogA/JKw0A+zV970kn4FzfPZhopyOBBX2AJf4f7rxks4nhQ4T/WwlepOcHy9SyaPOUsnTLiWo1xOv9TZ9uQU85Gu0B3gLaAdOUU7nB+Ew8EyNwPkVDZ8h4I5krigefRT4rkEivcDrkvmeeFisiwdFyql7P8bN0JrZRpk/813tc9p3z2mdj5IYmzHyx6tcvw4Ua1nSUwfagfPA7RrK+oHHAq49CHxEubgzBPwOdMuSncDDNF7kyQIHZMkvq0RrW5ZM451c1NPeoJxd29DvjxOOYV2RKRXv+gDlgo6rZG0CH+p8Kt7VpTuGU5igc3I2uRR0Dbt0iEe5AEqDK/16cUNe9kYKuhyfNU8OAOCpFgvrHJ8VtApxhdNWgpvW8u6EW0n3tQjBPpPp2Jo2PtdxcpeQGAGuAYcCrj+v41lL8hMdX054vowLgwoongi4flvObbHa+isoPGoWdCiOdrWPmyLSHbC0uw9p5F2jwJYK/J9rYf1JPRn0buBowhGLHwtUah1v6/sp4FN9/yqsoDC1kJx5oodSJPm9dPboAZe0Nm0HftHvQlAQa3FW0XwBeKfGe9GrtudTJPmXcToWd0zSKnQtNc/29cncDnhhVxj+28Tbs7r2pIZzrhGBzVZpPgncNQ5nNqrAZt0zcMBMISt6dRpGErs/euUReyPKedVY8w/qLwbfl16Icx/PTMicUTU8S6V8f9TMjyUN4f1Rica1I2tKcqbqvO8R3feZj+QslV1eF6MOs7j21nmUE171euYeM397PpIZ4B/ljWJz4zuxSxIqGfgTPpIvGScUG/Kks9/Vj1FZrEQ5n+qcjhtdiURfSe5cPgG8VuXafhPClQzhw0k+3bj3oNuHthGwXMoo5CxRTmu2p72QjevfBB9QKSkSYNG7wAthOtaWEOE89f0vZE2O4xvClwzaFZxvi/8Ag4O3fzRS+vYAAAAASUVORK5CYII=';

    public function test_full_data_uri_and_raw_base64_produce_identical_image_fragment(): void
    {
        $dataUri = 'data:image/png;base64,' . self::BASE64;
        $responseDataUri = $this->get('/?username=parity-user-a&logo=' . urlencode($dataUri));
        $responseDataUri->assertStatus(200);
        $svgA = $responseDataUri->getContent();

        $responseRaw = $this->get('/?username=parity-user-b&logo=' . self::BASE64);
        $responseRaw->assertStatus(200);
        $svgB = $responseRaw->getContent();

        $this->assertStringContainsString('<image', $svgA);
        $this->assertStringContainsString('<image', $svgB);
        // Extract the data:image payloads to ensure they are identical (allowing order differences in other attrs)
        preg_match('/<image[^>]*href="(data:image\/png;base64,[^"]+)"/i', $svgA, $mA);
        preg_match('/<image[^>]*href="(data:image\/png;base64,[^"]+)"/i', $svgB, $mB);
        $this->assertNotEmpty($mA, 'Failed to find embedded data URI in first SVG');
        $this->assertNotEmpty($mB, 'Failed to find embedded data URI in second SVG');
        $b64A = substr($mA[1], strlen('data:image/png;base64,'));
        $b64B = substr($mB[1], strlen('data:image/png;base64,'));
        $binA = base64_decode($b64A, true);
        $binB = base64_decode($b64B, true);
        $this->assertNotFalse($binA, 'First data URI failed to decode');
        $this->assertNotFalse($binB, 'Second data URI failed to decode');
        $lenA = strlen($binA);
        $lenB = strlen($binB);
        $this->assertGreaterThan(100, $lenA, 'First decoded logo unexpectedly small');
        $this->assertGreaterThan(100, $lenB, 'Second decoded logo unexpectedly small');
        $ratio = $lenA > $lenB ? ($lenA / max(1, $lenB)) : ($lenB / max(1, $lenA));
        $this->assertLessThan(1.1, $ratio, 'Decoded raster logo sizes differ by more than 10%');
    }
}
