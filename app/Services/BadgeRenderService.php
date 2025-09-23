<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PUGX\Poser\Badge; // for DEFAULT_FORMAT constant
use PUGX\Poser\Poser;
use PUGX\Poser\Render\SvgFlatRender;           // style: flat
use PUGX\Poser\Render\SvgFlatSquareRender;     // style: flat-square
use PUGX\Poser\Render\SvgForTheBadgeRenderer;  // style: for-the-badge
use PUGX\Poser\Render\SvgPlasticRender;        // style: plastic
use PUGX\Poser\Calculator\SvgTextSizeCalculator; // text size calculator

/**
 * Service responsible for generating and post-processing badge SVGs.
 * Handles: counts, abbreviation, label color overrides, logo embedding & recoloring,
 * geometry-safe width shifting, and robust data URI canonicalization.
 */
class BadgeRenderService
{
    private Poser $poser;

    /**
     * Abbreviation suffixes for thousands+ formatting.
     * @var array<int,string>
     */
    private static array $abbreviations = ['', 'K', 'M', 'B', 'T', 'Qa', 'Qi'];

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

        // Embed logo BEFORE recoloring to ensure geometry detection uses original label rect fill.
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
        $labelPattern = '/(<rect[^>]*fill="#555"[^>]*>)/';
        if (preg_match($labelPattern, $svg)) {
            return preg_replace_callback($labelPattern, function (array $m) use ($hexColor): string {
                $replaced = preg_replace('/fill="#555"/', 'fill="#' . $hexColor . '"', $m[0], 1);
                return is_string($replaced) ? $replaced : $m[0];
            }, $svg, 1) ?? $svg;
        }
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
            if ($prepared === null) {
                $this->debugLog('applyLogo:prepare-empty', ['reason' => 'no prepared dataUri']);
                $decoded = urldecode($logo);
                if (str_starts_with($decoded, 'data:image/')) {
                    if (str_contains($decoded, ';base64,') && strlen($decoded) < 12000) {
                        $dataUri = $this->canonicalizeDataUri($decoded);
                        $this->debugLog('applyLogo:fallback-data-uri', [
                            'decoded_length' => strlen($decoded),
                            'canonical_length' => strlen($dataUri),
                        ]);
                        $width = 14;
                        $height = 14;
                        return $this->embedLogoInSvg($svg, $dataUri, 'png', $width, $height);
                    }
                }
                if (! str_starts_with($logo, 'data:')) {
                    $rawNorm = LogoDataHelper::normalizeRawBase64($logo);
                    if ($rawNorm !== null) {
                        $bin = base64_decode($rawNorm, true);
                        if ($bin !== false && $bin !== '') {
                            $mime = LogoDataHelper::inferMime($bin) ?? '';
                            if ($mime !== '') {
                                $maxBytes = $this->safeConfig('badge.logo_max_bytes', 10000);
                                if (LogoDataHelper::withinSize($bin, $maxBytes)) {
                                    if ($mime === 'svg+xml') {
                                        $san = LogoDataHelper::sanitizeSvg($bin);
                                        if ($san === null) {
                                            $this->debugLog('applyLogo:raw-base64-sanitization-failed');
                                            return $svg;
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
                                    return $this->embedLogoInSvg($svg, $dataUri, $mime, $width, $height);
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
                $binLen = strlen($prepared['binary']);
                if ($binLen > $maxBytes) {
                    $this->debugLog('applyLogo:prepared-too-large', ['bytes' => $binLen, 'max' => $maxBytes]);
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
            if ($mime !== 'svg+xml' && isset($prepared['binary'])) {
                $dataUri = $this->canonicalizeDataUri('data:image/' . $mime . ';base64,' . base64_encode($prepared['binary']));
                $this->debugLog('applyLogo:raster-canonicalized', ['new_length' => strlen($dataUri)]);
            }
            if ($logoColor === 'auto' && $mime === 'svg+xml') {
                $logoColor = $this->deriveAutoLogoColor(svg: $svg, providedLabelColor: $labelColor, messageBackgroundFill: $messageBackgroundFill);
                $this->debugLog('applyLogo:logoColor-auto-derived', ['derived' => $logoColor]);
            }
            if ($mime === 'svg+xml' && $logoColor === null && ! str_starts_with($logo, 'data:')) {
                $logoColor = 'f5f5f5';
            }
            if ($logoColor && $mime === 'svg+xml') {
                $decodedSvg = null;
                if (isset($prepared['binary'])) {
                    $decodedSvg = $prepared['binary'];
                } elseif (preg_match('#^data:image/svg\+xml;base64,([A-Za-z0-9+/=]+)$#', $dataUri, $m)) {
                    $decodedSvg = base64_decode($m[1], true) ?: null;
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
        if ($dataUri && strpos($svg, '<image') === false) {
            $useMime = $mime !== '' ? $mime : 'png';
            $svg = $this->embedLogoInSvg($svg, $dataUri, $useMime, $width ?: 14, $height ?: 14);
            $this->debugLog('applyLogo:post-fallback-embed');
        }
        return $svg;
    }

    private function canonicalizeDataUri(string $dataUri): string
    {
        if (! str_starts_with($dataUri, 'data:image/') || ! str_contains($dataUri, ';base64,')) {
            return $dataUri;
        }
        [$prefix, $payload] = explode(';base64,', $dataUri, 2);
        $sanitized = str_replace(' ', '+', $payload);
        $sanitized = preg_replace('/\s+/', '', $sanitized) ?? $sanitized;
        if ($sanitized === '') {
            return $dataUri;
        }
        $mutated = $sanitized !== $payload;
        if (! preg_match('/^[A-Za-z0-9+\/]+=*$/', $sanitized)) {
            if ($mutated) {
                $this->debugLog('canonicalize:invalid-alphabet', [
                    'original_len' => strlen($payload),
                    'sanitized_len' => strlen($sanitized),
                ]);
            }
            return $prefix . ';base64,' . $sanitized;
        }
        $bin = base64_decode($sanitized, true);
        if ($bin === false || $bin === '') {
            if ($mutated) {
                $this->debugLog('canonicalize:decode-failed');
            }
            return $prefix . ';base64,' . $sanitized;
        }
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
     * Safely retrieve config value.
     * @template T
     * @param string $key
     * @param T $default
     * @return T
     */
    private function safeConfig(string $key, mixed $default): mixed
    {
        if (function_exists('config')) {
            try {
                return config($key, $default);
            } catch (\Throwable $e) {
            }
        }
        return $default;
    }

    private function deriveAutoLogoColor(string $svg, ?string $providedLabelColor, ?string $messageBackgroundFill): string
    {
        $hex = null;
        if ($providedLabelColor) {
            $hex = $this->getHexColor($providedLabelColor);
        } elseif (preg_match('/<rect[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', $svg, $m)) {
            $hex = strtolower($m[1]);
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

    /** @return array{0:int,1:int,2:int} */
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

    private function recolorSvg(string $svg, string $hex): ?string
    {
        $original = $svg;
        if (stripos($svg, '<svg') === false) {
            return null;
        }
        $changed = false;
        $hex = '#' . ltrim($hex, '#');
        if (stripos($svg, 'currentColor') !== false) {
            if (! preg_match('/<svg[^>]*fill="/i', $svg)) {
                $svg = preg_replace('/<svg(\s+)/i', '<svg$1fill="' . $hex . '" ', $svg, 1) ?? $svg;
                $changed = true;
            }
            $after = preg_replace('/currentColor/i', $hex, $svg) ?? $svg;
            if ($after !== $svg) {
                $svg = $after;
                $changed = true;
            }
        } else {
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
            if (! $changed && preg_match('/<path[^>]*>/i', $svg)) {
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
        return $colorMap[strtolower($color)] ?? '007ec6';
    }

    private function embedLogoInSvg(string $svg, string $logoDataUri, string $mime, int $width = 16, int $height = 16): string
    {
        $this->debugLog('embedLogoInSvg:start', [
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
        ]);
        $badgeHeight = 20.0;
        if (preg_match('/<svg[^>]*height="([0-9.]+)"/i', $svg, $hm)) {
            $badgeHeight = (float) $hm[1];
        }
        // Center logo vertically (after clamping height relative to badge height)
        if ($height > $badgeHeight - 2) {
            $height = (int) max(1, $badgeHeight - 2);
        }
        $y = (int) round(($badgeHeight - $height) / 2);
        $padLeft = 10;
        $padRight = 0;
        $segment = $padLeft + $width + $padRight;

        $parser = new BadgeGeometryParser();
        $parsed = $parser->parse($svg);
        if ($parsed->success) {
            $this->debugLog('embedLogoInSvg:parser-success');
            $totalWidth = (float) $parsed->totalWidth; // parser guarantees non-null
            $labelWidth = (float) $parsed->labelWidth;
            $statusWidth = (float) $parsed->statusWidth;
            $statusX = (float) $parsed->statusX;
        } else {
            // Fallback to legacy regex path (retain previous tolerant behavior)
            $this->debugLog('embedLogoInSvg:parser-fallback', ['reason' => $parsed->reason]);
            if (! preg_match('/<svg[^>]*width="([0-9.]+)"/i', $svg, $mTotal)) {
                $this->debugLog('embedLogoInSvg:missing-total-width');
                return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
            }
            $totalWidth = (float) $mTotal[1];
            if (! preg_match('/<rect[^>]*fill="#555"[^>]*width="([0-9.]+)"[^>]*>/', $svg, $mLabel) &&
                ! preg_match('/<rect[^>]*width="([0-9.]+)"[^>]*fill="#555"[^>]*>/', $svg, $mLabel)
            ) {
                $this->debugLog('embedLogoInSvg:missing-label-rect');
                return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
            }
            $labelWidth = (float) $mLabel[1];
            if (! preg_match('/<rect[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*>/', $svg, $mStatus) &&
                ! preg_match('/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', $svg, $mStatus)
            ) {
                $this->debugLog('embedLogoInSvg:missing-status-rect');
                return $this->simpleInjectLogo($svg, $logoDataUri, $width, $height, $y);
            }
            if (preg_match('/^<rect[^>]*fill="#/i', $mStatus[0])) {
                $statusX = (float) $mStatus[2];
                $statusWidth = (float) $mStatus[3];
            } else {
                $statusX = (float) $mStatus[1];
                $statusWidth = (float) $mStatus[2];
            }
            $combined = $labelWidth + $statusWidth;
            $delta = abs($combined - $totalWidth);
            if ($delta > max(0.05, $totalWidth * 0.1)) {
                $this->debugLog('embedLogoInSvg:width-delta-large', [
                    'labelWidth' => $labelWidth,
                    'statusWidth' => $statusWidth,
                    'totalWidth' => $totalWidth,
                    'combined' => $combined,
                    'delta' => $delta,
                ]);
            }
        }

        $svg = $this->shiftGeometry(
            svg: $svg,
            totalWidth: $totalWidth,
            labelWidth: $labelWidth,
            statusX: $statusX,
            statusWidth: $statusWidth,
            segment: $segment,
            badgeHeight: $badgeHeight,
        );
        $logoElement = '<image x="' . $padLeft . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . $logoDataUri . '" />';
        if (preg_match('/<g mask="url\(#a\)">/i', $svg, $gMatch, PREG_OFFSET_CAPTURE)) {
            $insertPos = $gMatch[0][1] + strlen($gMatch[0][0]);
            $svg = substr($svg, 0, $insertPos) . $logoElement . substr($svg, $insertPos);
        } else {
            $svg = str_replace('</svg>', $logoElement . '</svg>', $svg);
        }
        $this->debugLog('embedLogoInSvg:success', [
            'new_total_width' => $totalWidth + $segment,
            'segment_added' => $segment,
        ]);
        return $svg;
    }

    /**
     * Apply geometry shifts to expand left segment for logo insertion.
     * @param float $totalWidth Original total width of badge
     * @param float $labelWidth Width of label segment (to be expanded)
     * @param float $statusX X coordinate of status rect (shifted right)
     * @param float $statusWidth Width of status rect (unchanged)
     * @param float $segment Width to insert (logo padding + logo + padding)
     * @param float $badgeHeight Height of the badge for rect matching
     */
    private function shiftGeometry(string $svg, float $totalWidth, float $labelWidth, float $statusX, float $statusWidth, float $segment, float $badgeHeight): string
    {
        $newTotal = $totalWidth + $segment;
        $newLabelWidth = $labelWidth + $segment;
        $newStatusX = $statusX + $segment;
        // Adjust <svg width>
        $svg = preg_replace_callback('/<svg([^>]*)>/', function (array $m) use ($totalWidth, $newTotal) {
            $seg = $m[0];
            if (preg_match('/width="([0-9.]+)"/', $seg, $mw) && (float) $mw[1] === $totalWidth) {
                $seg = preg_replace('/width="' . preg_quote((string) $totalWidth, '/') . '"/', 'width="' . $newTotal . '"', $seg, 1) ?? $seg;
            }
            return $seg;
        }, $svg, 1) ?? $svg;
        // Adjust background rect(s)
        $svg = preg_replace('/<rect([^>]*?)width="' . preg_quote((string) $totalWidth, '/') . '"([^>]*?)height="' . $badgeHeight . '"([^>]*?)fill="#fff"\/>/', '<rect$1width="' . $newTotal . '"$2height="' . $badgeHeight . '"$3fill="#fff"/>', $svg, 1) ?? $svg;
        $svg = preg_replace('/<rect([^>]*?)width="' . preg_quote((string) $totalWidth, '/') . '"([^>]*?)height="' . $badgeHeight . '"([^>]*?)fill="url\(#b\)"\/>/', '<rect$1width="' . $newTotal . '"$2height="' . $badgeHeight . '"$3fill="url(#b)"/>', $svg, 1) ?? $svg;
        // Update label rect
        $labelRectUpdated = false;
        $svg = preg_replace_callback('/<rect[^>]*fill="#555"[^>]*>/', function (array $m) use ($labelWidth, $newLabelWidth, &$labelRectUpdated) {
            if ($labelRectUpdated) {
                return $m[0];
            }
            if (!preg_match('/width="([0-9.]+)"/', $m[0], $mw)) {
                return $m[0];
            }
            if ((float) $mw[1] !== $labelWidth) {
                return $m[0];
            }
            $labelRectUpdated = true;
            return preg_replace('/width="' . preg_quote($mw[1], '/') . '"/', 'width="' . $newLabelWidth . '"', $m[0], 1) ?? $m[0];
        }, $svg, 1) ?? $svg;
        // Shift status rect
        $statusRectUpdated = false;
        $svg = preg_replace_callback('/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', function (array $m) use ($statusX, $statusWidth, $newStatusX, &$statusRectUpdated) {
            if ($statusRectUpdated) {
                return $m[0];
            }
            if ((float) $m[1] !== $statusX || (float) $m[2] !== $statusWidth) {
                return $m[0];
            }
            $statusRectUpdated = true;
            return preg_replace('/x="' . preg_quote($m[1], '/') . '"/', 'x="' . $newStatusX . '"', $m[0], 1) ?? $m[0];
        }, $svg, 1) ?? $svg;
        // Shift all text x positions right
        $svg = preg_replace_callback('/<text x="([0-9.]+)" y="([0-9.]+)"([^>]*)>/', function (array $m) use ($segment): string {
            $newX = (float) $m[1] + $segment;
            return '<text x="' . $newX . '" y="' . $m[2] . '"' . $m[3] . '>';
        }, $svg) ?? $svg;
        return $svg;
    }

    private function simpleInjectLogo(string $svg, string $logoDataUri, int $width, int $height, int $y): string
    {
        $this->debugLog('simpleInjectLogo', [
            'width' => $width,
            'height' => $height,
        ]);
        $fallback = '<image x="4" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . $logoDataUri . '" />';
        if (str_contains($svg, '</svg>')) {
            return str_replace('</svg>', $fallback . '</svg>', $svg);
        }
        return $svg . $fallback;
    }

    /**
     * Internal structured debug logging gated by env/config.
     * @param array<string,mixed> $context
     */
    private function debugLog(string $event, array $context = []): void
    {
        // Read environment first (runtime unknown to static analyzer) then fallback to config.
        /** @var string|false $envVal */
        $envVal = getenv('BADGE_DEBUG_LOG');
        if ($envVal !== false) {
            $enabledRaw = $envVal; // string form
        } else {
            try {
                $enabledRaw = $this->safeConfig('badge.debug_logging', false); // bool fallback
            } catch (\Throwable $e) {
                return; // cannot resolve
            }
        }
        $enabled = false;
        if (is_bool($enabledRaw)) {
            $enabled = $enabledRaw;
        } elseif (is_string($enabledRaw)) {
            $normalized = strtolower(trim($enabledRaw));
            if ($normalized === '') {
                $enabled = false;
            } elseif (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                $enabled = true;
            } elseif (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                $enabled = false;
            } else {
                // Non-empty arbitrary string: treat as enabled for explicit intent.
                $enabled = true;
            }
        }
        if (!$enabled) {
            return;
        }
        foreach ($context as $k => $v) {
            if (is_string($v) && strlen($v) > 256) {
                $context[$k] = substr($v, 0, 252) . '...';
            }
        }
        try {
            Log::debug('[BadgeRender] ' . $event, $context);
        } catch (\Throwable $e) {
            // swallow logging backend issues
        }
    }
}
