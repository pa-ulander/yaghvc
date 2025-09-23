<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PUGX\Poser\Badge;
use PUGX\Poser\Calculator\SvgTextSizeCalculator;
use PUGX\Poser\Poser;
use PUGX\Poser\Render\SvgFlatRender;
use PUGX\Poser\Render\SvgFlatSquareRender;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;
use PUGX\Poser\Render\SvgPlasticRender;

class BadgeRenderService
{
    private Poser $poser;

    private static array $abbreviations = ['', 'K', 'M', 'B', 'T', 'Qa', 'Qi'];

    // (Diagnostic fields removed during cleanup)

    public function __construct()
    {
        $this->poser = new Poser([
            new SvgPlasticRender(
                textSizeCalculator: new SvgTextSizeCalculator,
            ),
            new SvgFlatRender(
                textSizeCalculator: new SvgTextSizeCalculator,
            ),
            new SvgFlatSquareRender(
                textSizeCalculator: new SvgTextSizeCalculator,
            ),
            new SvgForTheBadgeRenderer(
                textSizeCalculator: new SvgTextSizeCalculator,
            ),
        ]);
    }

    public function renderBadgeWithCount(
        string $label,
        int $count,
        string $messageBackgroundFill,
        string $badgeStyle,
        bool $abbreviated,
        ?string $labelColor = null,
        ?string $logoColor = null,
        ?string $logo = null,
        ?string $logoSize = null,
    ): string {
        $message = $this->formatNumber(number: $count, abbreviated: $abbreviated);

        return $this->renderBadge(
            label: $label,
            message: $message,
            messageBackgroundFill: $messageBackgroundFill,
            badgeStyle: $badgeStyle,
            labelColor: $labelColor,
            logoColor: $logoColor,
            logo: $logo,
            logoSize: $logoSize,
        );
    }

    public function renderBadgeWithError(
        string $label,
        string $message,
        string $badgeStyle,
    ): string {
        $messageBackgroundFill = 'red';

        return $this->renderBadge(
            label: $label,
            message: $message,
            messageBackgroundFill: $messageBackgroundFill,
            badgeStyle: $badgeStyle,
        );
    }

    public function renderPixel(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"/>';
    }

    private function renderBadge(
        string $label,
        string $message,
        string $messageBackgroundFill,
        string $badgeStyle,
        ?string $labelColor = null,
        ?string $logoColor = null,
        ?string $logo = null,
        ?string $logoSize = null,
    ): string {
        $svg = (string) $this->poser->generate(
            subject: $label,
            status: $message,
            color: $messageBackgroundFill,
            style: $badgeStyle,
            format: Badge::DEFAULT_FORMAT,
        );

        // Bug fix: logo failed to render if labelColor set because geometry
        // detection expected original fill="#555"; embed logo BEFORE recoloring label.
        if ($logo) {
            $svg = $this->applyLogo($svg, $logo, $logoSize, $logoColor, $labelColor, $messageBackgroundFill);
        }
        if ($labelColor) {
            $svg = $this->applyLabelColor($svg, $labelColor);
        }

        return $svg;
    }

    private function formatNumber(
        int $number,
        bool $abbreviated,
    ): string {
        if ($abbreviated) {
            return $this->formatAbbreviatedNumber(number: $number);
        }

        $reversedString = strrev(string: strval(value: $number));
        $formattedNumber = implode(separator: ',', array: str_split(string: $reversedString, length: 3));

        return strrev(string: $formattedNumber);
    }

    public function formatAbbreviatedNumber(int $number): string
    {
        $abbreviationIndex = 0;

        while ($number >= 1000) {
            $number /= 1000;
            $abbreviationIndex++;
        }

        return round(num: $number, precision: 1) . self::$abbreviations[$abbreviationIndex];
    }

    private function applyLabelColor(string $svg, string $labelColor): string
    {
        $hexColor = $this->getHexColor($labelColor);
        // Prefer targeting the original label rect (fill="#555") to avoid recoloring mask/gradient rects
        $labelPattern = '/(<rect[^>]*fill="#555"[^>]*>)/';
        if (preg_match($labelPattern, $svg)) {
            return preg_replace_callback($labelPattern, function (array $m) use ($hexColor): string {
                $replaced = preg_replace('/fill="#555"/', 'fill="#' . $hexColor . '"', $m[0], 1);

                return is_string($replaced) ? $replaced : $m[0];
            }, $svg, 1) ?? $svg;
        }
        // Fallback: replace first rect fill
        $genericPattern = '/(<rect[^>]*)(fill="[^"]*")([^>]*>)/';
        $replacement = '$1fill="#' . $hexColor . '"$3';

        return preg_replace($genericPattern, $replacement, $svg, 1) ?? $svg;
    }

    private function applyLogo(string $svg, string $logo, ?string $logoSize = null, ?string $logoColor = null, ?string $labelColor = null, ?string $messageBackgroundFill = null): string
    {
        $this->debugLog('applyLogo:start', [
            'input_length' => strlen($logo),
            'logo_starts_with' => substr($logo, 0, 24),
            'has_data_prefix' => str_starts_with($logo, 'data:'),
            'size_param' => $logoSize,
            'logo_color' => $logoColor,
        ]);
        $dataUri = null;
        $width = 0;
        $height = 0;
        $mime = '';
        try {
            $processor = new LogoProcessor;
            $prepared = $processor->prepare($logo, $logoSize);
            if (! $prepared || ! isset($prepared['dataUri'])) {
                $this->debugLog('applyLogo:prepare-empty', ['reason' => 'no prepared dataUri']);
                // Fallback 1: treat as data URI if it already looks like one (existing logic below)
                // Salvage: if the raw (possibly urlencoded) value looks like a data URI, inject minimally
                $decoded = urldecode($logo);
                if (str_starts_with($decoded, 'data:image/')) {
                    // Basic shape check: must contain ';base64,' and small enough
                    if (str_contains($decoded, ';base64,') && strlen($decoded) < 12000) { // widen guard
                        $dataUri = $this->canonicalizeDataUri($decoded);
                        $this->debugLog('applyLogo:fallback-data-uri', [
                            'decoded_length' => strlen($decoded),
                            'canonical_length' => strlen($dataUri),
                        ]);
                        // Use geometry-aware embedding instead of raw injection to avoid text overlap.
                        $width = 14;
                        $height = 14; // default square
                        return $this->embedLogoInSvg($svg, $dataUri, 'png', $width, $height);
                    }
                }

                // Fallback 2: raw base64 blob (no data: prefix) – attempt on-the-fly wrap
                // Use helper normalization (handles spaces vs '+') instead of brittle regex on urldecoded value.
                if (!str_starts_with($logo, 'data:')) {
                    $rawNorm = \App\Services\LogoDataHelper::normalizeRawBase64($logo);
                    if ($rawNorm !== null) {
                        $bin = base64_decode($rawNorm, true);
                        if ($bin !== false && $bin !== '') {
                            $mime = \App\Services\LogoDataHelper::inferMime($bin);
                            if ($mime !== null) {
                                $maxBytes = $this->safeConfig('badge.logo_max_bytes', 10000);
                                if (\App\Services\LogoDataHelper::withinSize($bin, $maxBytes)) {
                                    if ($mime === 'svg+xml') {
                                        $san = \App\Services\LogoDataHelper::sanitizeSvg($bin);
                                        if ($san === null) {
                                            $this->debugLog('applyLogo:raw-base64-sanitization-failed');
                                            return $svg; // unsafe
                                        }
                                        $bin = $san;
                                    }
                                    $dataUri = $this->canonicalizeDataUri('data:image/' . $mime . ';base64,' . base64_encode($bin));
                                    $this->debugLog('applyLogo:raw-base64-success', [
                                        'mime' => $mime,
                                        'data_uri_length' => strlen($dataUri),
                                    ]);
                                    $width = 14;
                                    $height = 14;
                                    $svg = $this->embedLogoInSvg($svg, $dataUri, $mime, $width, $height);
                                    return $svg;
                                }
                            }
                        }
                    } else {
                        $this->debugLog('applyLogo:raw-base64-normalization-failed');
                    }
                }

                return $svg; // degrade silently
            }
            if (isset($prepared['binary'])) {
                $maxBytes = $this->safeConfig('badge.logo_max_bytes', 10000);
                if (strlen($prepared['binary']) > $maxBytes) {
                    $this->debugLog('applyLogo:prepared-too-large', ['bytes' => strlen($prepared['binary']), 'max' => $maxBytes]);
                    return $svg;
                }
            }
            $dataUri = $this->canonicalizeDataUri($prepared['dataUri']);
            $width = (int) $prepared['width'];
            $height = (int) $prepared['height'];
            $mime = (string) $prepared['mime'];
            $this->debugLog('applyLogo:prepared-success', [
                'mime' => $mime,
                'width' => $width,
                'height' => $height,
                'data_uri_length' => strlen($dataUri),
            ]);

            // Canonicalize raster data URIs so that raw base64 input and pre-built data URI
            // yield identical embedded base64 (decode then re-encode). SVG may be recolored later.
            if ($mime !== 'svg+xml' && isset($prepared['binary']) && is_string($prepared['binary'])) {
                $dataUri = $this->canonicalizeDataUri('data:image/' . $mime . ';base64,' . base64_encode($prepared['binary']));
                $this->debugLog('applyLogo:raster-canonicalized', ['new_length' => strlen($dataUri)]);
            }

            // Derive auto color BEFORE default slug fallback so explicit auto overrides default logic.
            if ($logoColor === 'auto' && $mime === 'svg+xml') {
                $logoColor = $this->deriveAutoLogoColor(svg: $svg, providedLabelColor: $labelColor, messageBackgroundFill: $messageBackgroundFill);
                $this->debugLog('applyLogo:logoColor-auto-derived', ['derived' => $logoColor]);
            }
            // Provide default color for simple-icons slug if none specified (slug => not data URI input)
            if ($mime === 'svg+xml' && $logoColor === null && ! str_starts_with($logo, 'data:')) {
                $logoColor = 'f5f5f5';
            }

            // Attempt recolor only for SVG mime and when logoColor provided.
            if ($logoColor && $mime === 'svg+xml') {
                $decodedSvg = null;
                if (isset($prepared['binary']) && is_string($prepared['binary'])) {
                    $decodedSvg = $prepared['binary'];
                } else {
                    // decode from data uri
                    if (preg_match('#^data:image/svg\+xml;base64,([A-Za-z0-9+/=]+)$#', $dataUri, $m)) {
                        $decodedSvg = base64_decode($m[1], true) ?: null;
                    }
                }
                if ($decodedSvg) {
                    $hex = $this->getHexColor($logoColor);
                    $recolored = $this->recolorSvg($decodedSvg, $hex);
                    if ($recolored !== null) {
                        $dataUri = $this->canonicalizeDataUri('data:image/svg+xml;base64,' . base64_encode($recolored));
                        $this->debugLog('applyLogo:recolor-success', ['hex' => $hex]);
                    }
                }
            }
            $svg = $this->embedLogoInSvg($svg, $dataUri, $mime, $width, $height);
            $this->debugLog('applyLogo:embed-complete');
        } catch (\Throwable $e) {
            $this->debugLog('applyLogo:exception', ['msg' => $e->getMessage()]);
            // On exception we still attempt a salvage path
            $decoded = urldecode($logo);
            if (str_starts_with($decoded, 'data:image/') && str_contains($decoded, ';base64,') && strlen($decoded) < 16000) {
                $dataUri = $this->canonicalizeDataUri($decoded);
                $width = 14;
                $height = 14;
                if (! str_contains($svg, '<image')) {
                    $fallbackImage = '<image x="4" y="2" width="' . $width . '" height="' . $height . '" href="' . $dataUri . '" />';
                    if (str_contains($svg, '</svg>')) {
                        $svg = str_replace('</svg>', $fallbackImage . '</svg>', $svg);
                    } else {
                        $svg .= $fallbackImage;
                    }
                    $this->debugLog('applyLogo:salvage-success');
                }
            }
        }
        // Deterministic fallback: if we have a data URI but no <image>, invoke geometry embedding
        if ($dataUri && strpos($svg, '<image') === false) {
            // Default mime guess if unknown
            $useMime = $mime !== '' ? $mime : 'png';
            $svg = $this->embedLogoInSvg($svg, $dataUri, $useMime, $width ?: 14, $height ?: 14);
            $this->debugLog('applyLogo:post-fallback-embed');
        }

        return $svg;
    }

    /**
     * Canonicalize a data URI by:
     *  - Ensuring it matches data:image/<mime>;base64,<payload>
     *  - Removing all whitespace within the base64 segment
     *  - Converting spaces to '+' (defensive) before stripping
     *  - Validating characters and re-encoding decoded binary to normalize padding
     */
    private function canonicalizeDataUri(string $dataUri): string
    {
        if (!str_starts_with($dataUri, 'data:image/') || !str_contains($dataUri, ';base64,')) {
            return $dataUri; // not a candidate
        }
        [$prefix, $payload] = explode(';base64,', $dataUri, 2);
        // Replace spaces -> '+' (transport artifact) then strip all ASCII whitespace
        $sanitized = str_replace(' ', '+', $payload);
        $sanitized = preg_replace('/\s+/', '', $sanitized) ?? $sanitized;
        if ($sanitized === '') {
            return $dataUri;
        }
        $mutated = $sanitized !== $payload;
        // If alphabet invalid OR decode fails we still return sanitized (space-free) version
        if (!preg_match('/^[A-Za-z0-9+\/]+=*$/', $sanitized)) {
            if ($mutated) {
                $this->debugLog('canonicalize:invalid-alphabet', [
                    'original_len' => strlen($payload),
                    'sanitized_len' => strlen($sanitized),
                ]);
            }
            return $prefix . ';base64,' . $sanitized; // best-effort cleanup
        }
        $bin = base64_decode($sanitized, true);
        if ($bin === false || $bin === '') {
            if ($mutated) {
                $this->debugLog('canonicalize:decode-failed');
            }
            return $prefix . ';base64,' . $sanitized;
        }
        // Re-encode for canonical padding & ordering
        $reencoded = base64_encode($bin);
        if ($mutated || $reencoded !== $sanitized) {
            $this->debugLog('canonicalize:reencoded', [
                'original_len' => strlen($payload),
                'sanitized_len' => strlen($sanitized),
                'reencoded_len' => strlen($reencoded),
            ]);
        }
        return $prefix . ';base64,' . $reencoded;
    }

    /**
     * Retrieve configuration value safely outside the full Laravel app context.
     * Falls back to default if helper or repository unavailable.
     * @param mixed $default
     * @return mixed
     */
    private function safeConfig(string $key, $default)
    {
        if (function_exists('config')) {
            try {
                return config($key, $default);
            } catch (\Throwable $e) {
                // swallow – CLI/debug context without container
            }
        }
        return $default;
    }

    /**
     * Determine a contrasting logo color when logoColor=auto.
     * Priority:
     *  1. Use explicit labelColor if provided (after mapping to hex) to compute contrast.
     *  2. Fallback to detecting original label rect fill (#555) if labelColor missing.
     *  3. If neither found, use message background fill.
     * Algorithm: relative luminance approximate via simple perceived brightness formula.
     * Threshold ~128 -> if background is dark choose light (#f5f5f5) else choose dark (#333333).
     */
    private function deriveAutoLogoColor(string $svg, ?string $providedLabelColor, ?string $messageBackgroundFill): string
    {
        $hex = null;
        if ($providedLabelColor) {
            $hex = $this->getHexColor($providedLabelColor);
        } else {
            // Try to extract existing label rect fill (pre labelColor replacement it's #555)
            if (preg_match('/<rect[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', $svg, $m)) {
                $hex = strtolower($m[1]);
            }
        }
        if ($hex === null && $messageBackgroundFill) {
            $hex = $this->normalizeColorToHex($messageBackgroundFill);
        }
        if ($hex === null) {
            $hex = '555555';
        }
        $rgb = $this->hexToRgb($hex);
        $brightness = (0.299 * $rgb[0]) + (0.587 * $rgb[1]) + (0.114 * $rgb[2]);

        return $brightness < 128 ? 'f5f5f5' : '333333';
    }

    private function normalizeColorToHex(string $color): string
    {
        $color = ltrim($color, '#');
        if (preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return strtolower($color);
        }
        return $this->getHexColor($color);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $int = hexdec(substr($hex, 0, 2));
        $int2 = hexdec(substr($hex, 2, 2));
        $int3 = hexdec(substr($hex, 4, 2));
        return [$int, $int2, $int3];
    }

    /**
     * Recolor SVG content by applying fill attribute.
     * Strategy:
     *  - If elements use currentColor, inject a top-level fill attr or replace currentColor tokens.
     *  - Else, replace existing fill="#..." values (excluding none/transparent) uniformly.
     *  - Ensure we do not alter gradients, masks, clipPaths or URLs referencing paints.
     * Returns null if no change performed (so caller can keep original).
     */
    private function recolorSvg(string $svg, string $hex): ?string
    {
        $original = $svg;
        // Quick guard: ensure we have an <svg>
        if (stripos($svg, '<svg') === false) {
            return null;
        }
        $changed = false;
        // Normalize hex to #xxxxxx
        $hex = '#' . ltrim($hex, '#');
        // If currentColor present, set color attribute on root or replace occurrences
        if (stripos($svg, 'currentColor') !== false) {
            // Add fill on root svg if not present
            if (! preg_match('/<svg[^>]*fill="/i', $svg)) {
                $svg = preg_replace('/<svg(\s+)/i', '<svg$1fill="' . $hex . '" ', $svg, 1) ?? $svg;
                $changed = true;
            }
            // Replace currentColor tokens in fills/strokes
            $after = preg_replace('/currentColor/i', $hex, $svg) ?? $svg;
            if ($after !== $svg) {
                $svg = $after;
                $changed = true;
            }
        } else {
            // Replace solid fills not referencing url(#...) or none/transparent.
            $after = preg_replace_callback('/fill="(#[0-9a-fA-F]{3,8})"/', function (array $m) use ($hex, &$changed) {
                if (in_array(strtolower($m[1]), ['none', 'transparent'], true)) {
                    return $m[0];
                }
                if ($m[1] === $hex) {
                    return $m[0];
                }
                $changed = true;

                return 'fill="' . $hex . '"';
            }, $svg) ?? $svg;
            $svg = $after;
            // If no fills found, attempt to inject fill into first path element
            if (! $changed && preg_match('/<path[^>]*>/i', $svg, $pm)) {
                $injected = preg_replace('/<path(\s+)/i', '<path$1fill="' . $hex . '" ', $svg, 1);
                if (is_string($injected)) {
                    $svg = $injected;
                    $changed = true;
                }
            }
        }
        if (! $changed || $svg === $original) {
            return null;
        }

        return $svg;
    }

    private function getHexColor(string $color): string
    {
        $color = ltrim($color, '#');

        if (preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return $color;
        }

        $colorMap = [
            'brightgreen' => '44cc11',
            'green' => '97ca00',
            'yellowgreen' => 'a4a61d',
            'yellow' => 'dfb317',
            'orange' => 'fe7d37',
            'red' => 'e05d44',
            'blue' => '007ec6',
            'lightgray' => '9f9f9f',
            'lightgrey' => '9f9f9f',
            'gray' => '555555',
            'grey' => '555555',
            'blueviolet' => '8a2be2',
            'success' => '97ca00',
            'important' => 'fe7d37',
            'critical' => 'e05d44',
            'informational' => '007ec6',
            'inactive' => '9f9f9f',
        ];

        return $colorMap[strtolower($color)] ?? '007ec6'; // Default to blue
    }

    private function embedLogoInSvg(string $svg, string $logoDataUri, string $mime, int $width = 16, int $height = 16): string
    {
        $this->debugLog('embedLogoInSvg:start', [
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
        ]);
        // 1. Determine badge base metrics
        $badgeHeight = 20;
        if (preg_match('/<svg[^>]*height="([0-9.]+)"/i', $svg, $hm)) {
            $badgeHeight = (float) $hm[1];
        }
        if ($height > $badgeHeight - 2) {
            $height = (int) max(1, $badgeHeight - 2);
        }
        $y = (int) round(($badgeHeight - $height) / 2);

        $padLeft = 10;   // space from left edge to logo
        $padRight = 0;  // space between logo and label text
        $segment = $padLeft + $width + $padRight; // horizontal space we must add to the label area

        // 2. Extract width & rect metrics from original badge BEFORE modifications
        if (! preg_match('/<svg[^>]*width="([0-9.]+)"/i', $svg, $mTotal)) {
            $this->debugLog('embedLogoInSvg:missing-total-width');
            // Geometry unexpected – fallback simple inject without width shifts
            return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
        }
        $totalWidth = (float) $mTotal[1];
        // Match label rect (fill #555) regardless of attribute order
        if (
            ! preg_match('/<rect[^>]*fill="#555"[^>]*width="([0-9.]+)"[^>]*>/', $svg, $mLabel) &&
            ! preg_match('/<rect[^>]*width="([0-9.]+)"[^>]*fill="#555"[^>]*>/', $svg, $mLabel)
        ) {
            $this->debugLog('embedLogoInSvg:missing-label-rect');
            return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
        }
        $labelWidth = (float) $mLabel[1];

        // Match status rect: has x attribute and a solid color fill (e.g. #97ca00)
        if (
            ! preg_match('/<rect[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*>/', $svg, $mStatus) &&
            ! preg_match('/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', $svg, $mStatus)
        ) {
            $this->debugLog('embedLogoInSvg:missing-status-rect');
            return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
        }
        // Normalize capture groups depending on which pattern matched
        if (count($mStatus) === 4 && str_starts_with($mStatus[0], '<rect') && isset($mStatus[1]) && strlen($mStatus[1]) <= 8) {
            // Pattern where fill captured first then x then width
            $statusX = (float) $mStatus[2];
            $statusWidth = (float) $mStatus[3];
        } else {
            $statusX = (float) $mStatus[1];
            $statusWidth = (float) $mStatus[2];
        }

        // Sanity: ensure pieces line up to total width
        if (abs(($labelWidth + $statusWidth) - $totalWidth) > 0.05) {
            $this->debugLog('embedLogoInSvg:width-mismatch', [
                'labelWidth' => $labelWidth,
                'statusWidth' => $statusWidth,
                'totalWidth' => $totalWidth,
            ]);
            return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
        }

        // 3. Compute new geometry
        $newTotal = $totalWidth + $segment;
        $newLabelWidth = $labelWidth + $segment;
        $newStatusX = $statusX + $segment;

        // 4. Apply geometry updates using targeted patterns
        // svg width (full attribute)
        $svg = preg_replace('/width="' . preg_quote((string) $totalWidth, '/') . '"(\s+height="[0-9.]+")/', 'width="' . $newTotal . '"$1', $svg, 1) ?? $svg;
        // mask rect width (line containing rx and fill #fff)
        $svg = preg_replace('/<rect width="' . preg_quote((string) $totalWidth, '/') . '" height="' . $badgeHeight . '" rx="3" fill="#fff"\/>/', '<rect width="' . $newTotal . '" height="' . $badgeHeight . '" rx="3" fill="#fff"/>', $svg, 1) ?? $svg;
        // gradient overlay rect width (fill url(#b))
        $svg = preg_replace('/<rect width="' . preg_quote((string) $totalWidth, '/') . '" height="' . $badgeHeight . '" fill="url\(#b\)"\/>/', '<rect width="' . $newTotal . '" height="' . $badgeHeight . '" fill="url(#b)"/>', $svg, 1) ?? $svg;
        // label rect width (no x, fill #555)
        $labelPattern = '/<rect width="([0-9.]+)" height="' . $badgeHeight . '" fill="#555"\/>/';
        $svg = preg_replace_callback($labelPattern, function (array $m) use ($labelWidth, $newLabelWidth): string {
            if ((float) $m[1] !== $labelWidth) {
                return $m[0];
            }

            return str_replace('width="' . $m[1] . '"', 'width="' . $newLabelWidth . '"', $m[0]);
        }, $svg, 1) ?? $svg;
        // status rect x update
        $statusPattern = '/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*height="' . $badgeHeight . '"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/';
        $svg = preg_replace_callback($statusPattern, function (array $m) use ($statusX, $statusWidth, $newStatusX): string {
            if ((float) $m[1] !== $statusX || (float) $m[2] !== $statusWidth) {
                return $m[0];
            }
            $out = $m[0];
            $out = preg_replace('/x="' . preg_quote($m[1], '\/') . '"/', 'x="' . $newStatusX . '"', $out, 1);

            return is_string($out) ? $out : $m[0];
        }, $svg, 1) ?? $svg;

        // 5. Shift text x positions by segment
        $svg = preg_replace_callback('/<text x="([0-9.]+)" y="([0-9.]+)"([^>]*)>/', function (array $m) use ($segment): string {
            $newX = (float) $m[1] + $segment;

            return '<text x="' . $newX . '" y="' . $m[2] . '"' . $m[3] . '>';
        }, $svg) ?? $svg;

        // 6. Insert logo element inside masked group before first rect (so logo appears "on top" of label background but under gradient)
        $logoElement = '<image x="' . $padLeft . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . $logoDataUri . '" />';
        if (preg_match('/<g mask="url\(#a\)">/i', $svg, $gMatch, PREG_OFFSET_CAPTURE)) {
            $insertPos = $gMatch[0][1] + strlen($gMatch[0][0]);
            $svg = substr($svg, 0, $insertPos) . $logoElement . substr($svg, $insertPos);
        } else {
            $svg = str_replace('</svg>', $logoElement . '</svg>', $svg);
        }
        $this->debugLog('embedLogoInSvg:success', [
            'new_total_width' => $newTotal,
            'segment_added' => $segment,
        ]);

        return $svg;
    }

    private function simpleInjectLogo(string $svg, string $logoDataUri, int $width, int $height, int $y): string
    {
        $this->debugLog('simpleInjectLogo', [
            'width' => $width,
            'height' => $height,
        ]);
        // Minimal fallback: insert before closing svg without changing geometry
        $fallback = '<image x="4" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . $logoDataUri . '" />';
        if (str_contains($svg, '</svg>')) {
            return str_replace('</svg>', $fallback . '</svg>', $svg);
        }

        return $svg . $fallback;
    }

    /**
     * Conditional debug logger for badge rendering internals.
     * Accepts scalar/array context; suppressed unless config('badge.debug_logging') true.
     */
    private function debugLog(string $event, array $context = []): void
    {
        try {
            $enabled = $this->safeConfig('badge.debug_logging', false);
            if (! $enabled) {
                return;
            }
            // Normalize potentially large payloads
            foreach ($context as $k => $v) {
                if (is_string($v) && strlen($v) > 256) {
                    $context[$k] = substr($v, 0, 252) . '...';
                }
            }
            Log::debug('[BadgeRender] ' . $event, $context);
        } catch (\Throwable $e) {
            // swallow – logging must never break badge rendering
        }
    }
}
