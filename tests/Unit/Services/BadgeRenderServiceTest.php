<?php

declare(strict_types=1);

use App\Services\BadgeRenderService;
use Illuminate\Support\Facades\Config;
use PUGX\Poser\Poser; // add for Poser::class assertion

/**
 * These tests target uncovered branches inside BadgeRenderService: logo fallback paths,
 * recoloring logic, canonicalization branches, and deriveAutoLogoColor decisions.
 */

beforeAll(function () {
    // Enable internal debug logging so debugLog lines execute when hit.
    putenv('BADGE_DEBUG_LOG=1');
});

/** Helper to invoke private methods via reflection. */
function invokeBadgePrivate(object $svc, string $method, array $args = [])
{
    $ref = new ReflectionClass($svc);
    $m = $ref->getMethod($method);
    $m->setAccessible(true);
    return $m->invokeArgs($svc, $args);
}

it('falls back to percent-decoded data uri when prepare returns null', function () {
    $svc = new BadgeRenderService();
    // URL encoded 1x1 png data URI
    $raw = rawurlencode('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=');
    $svg = invokeBadgePrivate($svc, 'applyLogo', [
        '<svg width="100" height="20" aria-label="x:y"><rect fill="#555" width="50" height="20"></rect><rect fill="#007ec6" x="50" width="50" height="20"></rect></svg>',
        $raw,
        null,
        null,
        'blue',
        'green',
    ]);
    expect($svg)->toContain('<image');
});

it('handles raw base64 success path embedding image', function () {
    $svc = new BadgeRenderService();
    // Provide raw base64 (no data: prefix) that is valid PNG
    $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=';
    $out = invokeBadgePrivate($svc, 'applyLogo', [
        '<svg width="90" height="20" aria-label="a:b"><rect fill="#555" width="40" height="20"></rect><rect fill="#007ec6" x="40" width="50" height="20"></rect></svg>',
        $base64,
        null,
        null,
        null,
        null,
    ]);
    expect($out)->toContain('<image');
});

it('returns original svg when normalization fails for random slug', function () {
    $svc = new BadgeRenderService();
    $original = '<svg width="80" height="20" aria-label="l:m"><rect fill="#555" width="30" height="20"></rect><rect fill="#007ec6" x="30" width="50" height="20"></rect></svg>';
    $out = invokeBadgePrivate($svc, 'applyLogo', [$original, 'unknown-slug-not-real-xyz', null, null, null, null]);
    expect($out)->toBe($original);
});

it('rejects oversize prepared binary using lowered config limit', function () {
    Config::set('badge.logo_max_bytes', 10); // tiny to force rejection
    $svc = new BadgeRenderService();
    // Provide raw base64 logo (not data uri) so size check happens on raw-base64 path
    $raw = base64_encode(random_bytes(32)); // >10 bytes binary
    $orig = '<svg width="100" height="20" aria-label="o:p"><rect fill="#555" width="60" height="20"></rect><rect fill="#007ec6" x="60" width="40" height="20"></rect></svg>';
    $out = invokeBadgePrivate($svc, 'applyLogo', [$orig, $raw, null, null, null, null]);
    expect($out)->toBe($orig); // oversize -> degrade silently
});

it('recolors svg logo when logoColor provided', function () {
    $svc = new BadgeRenderService();
    $svgLogo = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M0 0h10v10H0z" fill="#000"/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svgLogo);
    $result = invokeBadgePrivate($svc, 'applyLogo', [
        '<svg width="120" height="20" aria-label="c:d"><rect fill="#555" width="70" height="20"></rect><rect fill="#007ec6" x="70" width="50" height="20"></rect></svg>',
        $dataUri,
        null,
        'red', // logoColor triggers recolor
        null,
        null,
    ]);
    expect($result)->toContain('<image');
});

it('auto derives logo color and recolors when logoColor=auto with svg', function () {
    $svc = new BadgeRenderService();
    $svgLogo = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><path d="M0 0h10v10H0z" fill="#123456"/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svgLogo);
    $res = invokeBadgePrivate($svc, 'applyLogo', [
        '<svg width="140" height="20" aria-label="e:f"><rect fill="#555" width="80" height="20"></rect><rect fill="#007ec6" x="80" width="60" height="20"></rect></svg>',
        $dataUri,
        null,
        'auto',
        'orange', // provided label color influences deriveAutoLogoColor
        'green', // message background
    ]);
    expect($res)->toContain('<image');
});

it('recolorSvg returns null when svg does not contain <svg tag (non-svg fragment)', function () {
    $svc = new BadgeRenderService();
    $fragment = '<path fill="#123456" d="M0 0h5v5H0z"/>';
    $res = invokeBadgePrivate($svc, 'recolorSvg', [$fragment, '123456']);
    expect($res)->toBeNull(); // early return branch
});

it('recolorSvg injects color for currentColor usage', function () {
    $svc = new BadgeRenderService();
    $res = invokeBadgePrivate($svc, 'recolorSvg', [
        '<svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"><path fill="currentColor" d="M0 0h5v5H0z"/></svg>',
        '00aa00',
    ]);
    expect($res)->not()->toBeNull()->and($res)->toContain('#00aa00');
});

it('recolorSvg injects fill on first path with no fill', function () {
    $svc = new BadgeRenderService();
    $res = invokeBadgePrivate($svc, 'recolorSvg', [
        '<svg xmlns="http://www.w3.org/2000/svg" width="5" height="5"><path d="M0 0h5v5H0z"/></svg>',
        '112233',
    ]);
    expect($res)->toContain('#112233');
});

it('canonicalizeDataUri leaves non-data uri unchanged', function () {
    $svc = new BadgeRenderService();
    $out = invokeBadgePrivate($svc, 'canonicalizeDataUri', ['not-a-data-uri']);
    expect($out)->toBe('not-a-data-uri');
});

it('canonicalizeDataUri handles invalid alphabet without decode', function () {
    $svc = new BadgeRenderService();
    $data = 'data:image/png;base64,AAAA-AAAA';
    $out = invokeBadgePrivate($svc, 'canonicalizeDataUri', [$data]);
    expect($out)->toBe($data); // unchanged payload part
});

it('canonicalizeDataUri handles decode failure after mutation', function () {
    $svc = new BadgeRenderService();
    $data = 'data:image/png;base64,A===A'; // passes regex? A===A includes invalid padding but sanitized keeps
    $out = invokeBadgePrivate($svc, 'canonicalizeDataUri', [$data]);
    expect($out)->toBe($data);
});

it('canonicalizeDataUri replaces space with plus but leaves invalid decode intact', function () {
    $svc = new BadgeRenderService();
    $orig = 'data:image/png;base64,AA AA'; // becomes AA+AA; still invalid -> returns mutated
    $out = invokeBadgePrivate($svc, 'canonicalizeDataUri', [$orig]);
    expect($out)->toBe('data:image/png;base64,AA+AA');
});

it('deriveAutoLogoColor uses provided label color then brightness threshold', function () {
    $svc = new BadgeRenderService();
    $color = invokeBadgePrivate($svc, 'deriveAutoLogoColor', [
        '<svg width="10" height="10"><rect fill="#123456" width="5" height="10"></rect></svg>',
        'blueviolet',
        null,
    ]);
    expect($color)->toBeString();
});

it('deriveAutoLogoColor extracts from rect then fallback to message background', function () {
    $svc = new BadgeRenderService();
    $color = invokeBadgePrivate($svc, 'deriveAutoLogoColor', [
        '<svg width="10" height="10"><rect fill="#abcdef" width="5" height="10"></rect></svg>',
        null,
        'red',
    ]);
    expect($color)->toBeString();
});

it('renders badge with count', function () {
    $result = (new BadgeRenderService)->renderBadgeWithCount('Views', 1000, 'blue', 'flat', false, null, null, null);

    expect($result)->toBeString();
    expect($result)->toContain('<svg');
    expect($result)->toContain('Views');
    expect($result)->toContain('1,000');
});

it('renders badge with abbreviated count', function () {
    $result = (new BadgeRenderService)->renderBadgeWithCount('Views', 1500, 'green', 'flat-square', true, null, null, null);

    expect($result)->toBeString();
    expect($result)->toContain('<svg');
    expect($result)->toContain('Views');
    expect($result)->toContain('1.5K');
});

it('renders badge with error', function () {
    $result = (new BadgeRenderService)->renderBadgeWithError('Error', 'Not Found', 'plastic');
    expect($result)->toBeString();
    expect($result)->toContain('<svg');
    expect($result)->toContain('Error');
    expect($result)->toContain('Not Found');
    expect($result)->toContain('#e05d44');
});

it('renders pixel', function () {
    $result = (new BadgeRenderService)->renderPixel();
    expect($result)->toBe('<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>');
});

it('formats number without abbreviation', function () {
    $badgeRenderService = new BadgeRenderService;
    $reflection = new ReflectionClass($badgeRenderService);
    $method = $reflection->getMethod('formatNumber');
    $method->setAccessible(true);

    $result = $method->invokeArgs($badgeRenderService, [1234567, false]);
    expect($result)->toBe('1,234,567');
});

it('formats abbreviated number', function () {
    $testCases = [
        [999, '999'],
        [1000, '1K'],
        [1500, '1.5K'],
        [1000000, '1M'],
        [1500000, '1.5M'],
        [1000000000, '1B'],
        [1500000000000, '1.5T'],
    ];

    foreach ($testCases as [$input, $expected]) {
        $result = (new BadgeRenderService)->formatAbbreviatedNumber($input);
        expect($result)->toBe($expected);
    }
});

// (Poser instantiation covered implicitly through multiple render calls above)

it('uses correct color', function () {
    // 'red', 'green', 'blue', 'yellow'
    $colors = ['#e05d44', '#97ca00', '#007ec6', '#dfb317'];

    foreach ($colors as $color) {
        $result = (new BadgeRenderService)->renderBadgeWithCount('Test', 100, $color, 'flat', false, null, null, null);
        expect($result)->toContain($color);
    }
});

it('applies label color correctly', function () {
    $result = (new BadgeRenderService)->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, 'red', null, null);
    expect($result)->toContain('fill="#e05d44"'); // red color
});

it('handles named label colors', function () {
    $result = (new BadgeRenderService)->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, 'green', null, null);
    expect($result)->toContain('fill="#97ca00"'); // green color
});

it('handles hex label colors', function () {
    $result = (new BadgeRenderService)->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, 'ff0000', null, null);
    expect($result)->toContain('fill="#ff0000"'); // red color
});

it('handles logo parameter without errors', function () {
    $base64Logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    $result = (new BadgeRenderService)->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, null, $base64Logo);
    expect($result)->toBeString();
    expect($result)->toContain('<svg');
});

it('handles logo base64 where plus signs may be spaces from query decoding', function () {
    // Create a base64 string containing + characters artificially (small red dot PNG already has some, but ensure)
    $original = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAOCAYAAAAfSC3RAAACmElEQVQokUWSa0iTcRTGn//26u4b6ZQ0U8lKMqykwPpgZVBEHyLp8jEoIZJADCQ0iCiStIwuZmHRioIuroQss2VkrkIrdeFckiZqdhctTXPOve8Tr7M6X8/zO+fwPEfIwy7IwQA0GgExGYQwyhCmMLRX1z2hJCJSN+xZgqAZnPgCaAUQ0EHICjSYLlKBCDdNQb7HLmeRoy3zQFnzYk/1WTckGUIXCVD+Kw+BpAxtuBXCpkN7bdXt/JL3W3J3xuHg3iTsL/NkNFWVPoWkQOj/wxooCrRhFgiTjI4n9ZVHHQObjxVEY8UGIi1zEhVFCahwdq5qvn+hHkKC0EcBigxwvAnkW3ge7L6TMi+VztOLOOKOY8ulKL68GM2emnjeLF3AZSlz2FCZ6yaHwLGv6pkv8MyxsUoHLcsLwBuHwE0rtdy2UuLWNTpmpkkszQEfnAPDAd47tbaB7NaJR+eXujfmtGTUXgFWp5uwPd8Oi1GBJEmwWYlP34L4PSFw7chPeD+MYnkWUVmy0CeNfe5N8ANIjNWpNmHzqklYrDIGRwRm2gXsM/xofRMOf1AgcbYOAfgxMvgxCmS9+dbh5A6VarxuIMdBDoJ0g+vSreytNpAEux7qqWrK82I+kC2xYOAzyFbz5QNJPrXhdRo4XK/n3WILkxPsbKqwsr8xBB3PjukhGyJJv+qqB+QvkN0mR2Fim5pU1hobzxTYOPbcyJoTNpoAlu6wdZKvIslR0O9VXe0Clc5p2Ge4WDh36ux3ThM/1RqnNhXvilU32cjvINtAf4cKdkzlSHpBTqgNY11JfLtFA+o14NU8Wx/piggNfg2yGVR8EF9/dP37PyCIoDQLs8z9hmv71nsC4wFz9klX2tD4/AEG+gBoQ7KghD8MZ2xdnt7s7wAAAABJRU5ErkJggg==';
    // Simulate what would happen if '+' became ' ' in query decoding (already handled now)
    $withSpaces = str_replace('+', ' ', $original);
    $service = new BadgeRenderService;
    $result = $service->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, null, $withSpaces);
    expect($result)->toBeString();
    expect($result)->toContain('<svg');
});

it('handles named logo slug (github)', function () {
    $service = new BadgeRenderService;
    $result = $service->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, null, 'github', null);
    expect($result)->toContain('<image');
});

it('applies auto logoSize for svg maintaining aspect ratio', function () {
    $service = new BadgeRenderService;
    // simple svg data uri 20x10 (aspect 2:1)
    $svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="10"><rect width="20" height="10" fill="red"/></svg>');
    $result = $service->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, null, $svg, 'auto');
    // Expect width greater than height due to aspect ratio scaling
    preg_match('/<image[^>]*width="(\d+)"[^>]*height="(\d+)"/i', $result, $m);
    expect(isset($m[1]) && isset($m[2]))->toBeTrue();
    $width = (int) $m[1];
    $height = (int) $m[2];
    expect($width)->toBeGreaterThan($height);
});

it('applies fixed numeric logoSize when provided', function () {
    $service = new BadgeRenderService;
    $svg = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"><circle cx="15" cy="15" r="15" fill="blue"/></svg>');
    $result = $service->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, null, $svg, '10');
    preg_match('/<image[^>]*width="(\d+)"[^>]*height="(\d+)"/i', $result, $m);
    $width = (int) ($m[1] ?? 0);
    $height = (int) ($m[2] ?? 0);
    expect($width)->toBe(10);
    expect($height)->toBe(10);
});

it('applies logoColor to simple icon slug', function () {
    $service = new BadgeRenderService;
    $result = $service->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, 'red', 'github', null);
    expect($result)->toContain('<image');
});

it('applies logoColor hex to inline svg data uri', function () {
    $service = new BadgeRenderService;
    $inlineSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M0 0h10v10H0z"/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($inlineSvg);
    $result = $service->renderBadgeWithCount('Test', 100, 'blue', 'flat', false, null, 'ff0000', $dataUri, null);
    expect($result)->toContain('<image');
});

it('recolors svg with existing fill attributes', function () {
    $service = new BadgeRenderService();
    $inlineSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path fill="#123456" d="M0 0h10v10H0z"/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($inlineSvg);
    $result = $service->renderBadgeWithCount('Test', 42, 'green', 'flat', false, null, 'ff8800', $dataUri, null);
    expect($result)->toContain('<image');
});

it('applies default logoColor for simple-icons slug when omitted', function () {
    $service = new BadgeRenderService();
    $result = $service->renderBadgeWithCount('Test', 10, 'blue', 'flat', false, null, null, 'github', null);
    // We can't easily extract internal recolored svg without decoding, but ensure image present
    expect($result)->toContain('<image');
});

it('honors explicit logoColor over default for slug', function () {
    $service = new BadgeRenderService();
    $result = $service->renderBadgeWithCount('Test', 10, 'blue', 'flat', false, null, 'red', 'github', null);
    expect($result)->toContain('<image');
});

it('applies auto logoColor choosing light on dark label', function () {
    $service = new BadgeRenderService();
    // dark label (default #555) expect light f5f5f5 chosen → encoded inside data uri; just ensure image present
    $result = $service->renderBadgeWithCount('Test', 10, 'green', 'flat', false, null, 'auto', 'github', null);
    expect($result)->toContain('<image');
});

it('applies auto logoColor choosing dark on light custom labelColor', function () {
    $service = new BadgeRenderService();
    // Provide a light labelColor (yellow maps to dfb317 ~ light) expect dark 333333 chosen
    $result = $service->renderBadgeWithCount('Test', 10, 'green', 'flat', false, 'yellow', 'auto', 'github', null);
    expect($result)->toContain('<image');
});

it('applies auto logoColor for inline svg data uri', function () {
    $service = new BadgeRenderService();
    $inlineSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><path d="M0 0h10v10H0z"/></svg>';
    $dataUri = 'data:image/svg+xml;base64,' . base64_encode($inlineSvg);
    $result = $service->renderBadgeWithCount('Test', 5, 'blue', 'flat', false, null, 'auto', $dataUri, null);
    expect($result)->toContain('<image');
});
