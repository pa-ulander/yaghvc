<?php

declare(strict_types=1);

namespace App\Services;

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
        $dataUri = null;
        $width = 0;
        $height = 0;
        $mime = '';
        try {
            $processor = new LogoProcessor;
            $prepared = $processor->prepare($logo, $logoSize);
            if (! $prepared || ! isset($prepared['dataUri'])) {
                // Fallback 1: treat as data URI if it already looks like one (existing logic below)
                // Salvage: if the raw (possibly urlencoded) value looks like a data URI, inject minimally
                $decoded = urldecode($logo);
                if (str_starts_with($decoded, 'data:image/')) {
                    // Basic shape check: must contain ';base64,' and small enough
                    if (str_contains($decoded, ';base64,') && strlen($decoded) < 12000) { // widen guard
                        $dataUri = $decoded;
                        $width = 14;
                        $height = 14; // default square
                        // Inject directly at end
                        if (! str_contains($svg, '<image')) {
                            $fallbackImage = '<image x="4" y="2" width="' . $width . '" height="' . $height . '" href="' . $dataUri . '" />';
                            if (str_contains($svg, '</svg>')) {
                                $svg = str_replace('</svg>', $fallbackImage . '</svg>', $svg);
                            } else {
                                $svg .= $fallbackImage;
                            }
                        }

                        return $svg;
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
                                            return $svg; // unsafe
                                        }
                                        $bin = $san;
                                    }
                                    $dataUri = 'data:image/' . $mime . ';base64,' . base64_encode($bin);
                                    $width = 14;
                                    $height = 14;
                                    $svg = $this->embedLogoInSvg($svg, $dataUri, $mime, $width, $height);
                                    return $svg;
                                }
                            }
                        }
                    } else {
                    }
                }

                return $svg; // degrade silently
            }
            if (isset($prepared['binary'])) {
                $maxBytes = $this->safeConfig('badge.logo_max_bytes', 10000);
                if (strlen($prepared['binary']) > $maxBytes) {
                    return $svg;
                }
            }
            $dataUri = $prepared['dataUri'];
            $width = (int) $prepared['width'];
            $height = (int) $prepared['height'];
            $mime = (string) $prepared['mime'];

            // Derive auto color BEFORE default slug fallback so explicit auto overrides default logic.
            if ($logoColor === 'auto' && $mime === 'svg+xml') {
                $logoColor = $this->deriveAutoLogoColor(svg: $svg, providedLabelColor: $labelColor, messageBackgroundFill: $messageBackgroundFill);
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
                        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($recolored);
                    }
                }
            }
            $svg = $this->embedLogoInSvg($svg, $dataUri, $mime, $width, $height);
        } catch (\Throwable $e) {
            // On exception we still attempt a salvage path
            $decoded = urldecode($logo);
            if (str_starts_with($decoded, 'data:image/') && str_contains($decoded, ';base64,') && strlen($decoded) < 16000) {
                $dataUri = $decoded;
                $width = 14;
                $height = 14;
                if (! str_contains($svg, '<image')) {
                    $fallbackImage = '<image x="4" y="2" width="' . $width . '" height="' . $height . '" href="' . $dataUri . '" />';
                    if (str_contains($svg, '</svg>')) {
                        $svg = str_replace('</svg>', $fallbackImage . '</svg>', $svg);
                    } else {
                        $svg .= $fallbackImage;
                    }
                }
            }
        }
        // Deterministic fallback guaranteeing an <image> if we have a data URI
        if ($dataUri && strpos($svg, '<image') === false) {
            $fallbackImage = '<image x="4" y="2" width="' . ($width ?: 14) . '" height="' . ($height ?: 14) . '" href="' . $dataUri . '" />';
            if (str_contains($svg, '</svg>')) {
                $svg = str_replace('</svg>', $fallbackImage . '</svg>', $svg);
            } else {
                $svg .= $fallbackImage;
            }
        }

        return $svg;
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
            // Geometry unexpected – fallback simple inject without width shifts
            return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
        }
        $totalWidth = (float) $mTotal[1];
        if (! preg_match('/<rect[^>]*width="([0-9.]+)"[^>]*fill="#555"[^>]*>/', $svg, $mLabel)) {
            return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
        }
        $labelWidth = (float) $mLabel[1];

        // Match status rect: has x attribute and a solid color fill (e.g. #97ca00)
        if (! preg_match('/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', $svg, $mStatus)) {
            return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
        }
        $statusX = (float) $mStatus[1];
        $statusWidth = (float) $mStatus[2];

        // Sanity: ensure pieces line up to total width
        if (abs(($labelWidth + $statusWidth) - $totalWidth) > 0.05) {
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
        $statusPattern = '/<rect x="([0-9.]+)" width="([0-9.]+)" height="' . $badgeHeight . '" fill="#([0-9a-fA-F]{3,8})"\/>/';
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

        return $svg;
    }

    private function simpleInjectLogo(string $svg, string $logoDataUri, int $width, int $height, int $y): string
    {
        // Minimal fallback: insert before closing svg without changing geometry
        $fallback = '<image x="4" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . $logoDataUri . '" />';
        if (str_contains($svg, '</svg>')) {
            return str_replace('</svg>', $fallback . '</svg>', $svg);
        }

        return $svg . $fallback;
    }
}
