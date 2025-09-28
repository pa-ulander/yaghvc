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
 *
 * @package App\Services
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
        $this->poser = new Poser(renders: [
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
            $svg = $this->applyLogo(svg: $svg, logo: $logo, logoSize: $logoSize, logoColor: $logoColor, labelColor: $labelColor, messageBackgroundFill: $messageBackgroundFill);
        }
        if ($labelColor) {
            $svg = $this->applyLabelColor(svg: $svg, labelColor: $labelColor);
        }

        return $this->ensureAccessibleLabels(svg: $svg, label: $label, message: $message);
    }

    private function formatNumber(
        int $number,
        bool $abbreviated,
    ): string {
        if ($abbreviated) {
            return $this->formatAbbreviatedNumber(number: $number);
        }

        $reversedString = strrev(strval($number));
        $formattedNumber = implode(',', str_split($reversedString, 3));

        return strrev($formattedNumber);
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
        $hexColor = $this->getHexColor(color: $labelColor);
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

    private function ensureAccessibleLabels(string $svg, string $label, string $message): string
    {
        $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $ariaContent = $safeLabel . ': ' . $safeMessage;

        $updatedSvg = preg_replace('/aria-label="[^"]*"/', 'aria-label="' . $ariaContent . '"', $svg, 1);
        if ($updatedSvg !== null) {
            $svg = $updatedSvg;
        }

        $titlePattern = '/<title>[^<]*<\/title>/';
        if (preg_match($titlePattern, $svg)) {
            $replaced = preg_replace($titlePattern, '<title>' . $ariaContent . '</title>', $svg, 1);
            if ($replaced !== null) {
                return $replaced;
            }
            return $svg;
        }

        $injected = preg_replace('/<svg[^>]*>/', '$0<title>' . $ariaContent . '</title>', $svg, 1);
        return $injected !== null ? $injected : $svg;
    }

    private function applyLogo(string $svg, string $logo, ?string $logoSize = null, ?string $logoColor = null, ?string $labelColor = null, ?string $messageBackgroundFill = null): string
    {
        $this->debugLog(event: 'applyLogo:start', context: [
            'input_length' => strlen(string: $logo),
            'logo_starts_with' => substr(string: $logo, offset: 0, length: 24),
            'has_data_prefix' => str_starts_with(haystack: $logo, needle: 'data:'),
            'size_param' => $logoSize,
            'logo_color' => $logoColor,
        ]);
        $dataUri = null;
        $width = 0;
        $height = 0;
        $mime = '';
        try {
            $processor = new LogoProcessor;
            $prepared = $processor->prepare(raw: $logo, logoSize: $logoSize);
            if ($prepared === null) {
                $this->debugLog(event: 'applyLogo:prepare-empty', context: ['reason' => 'no prepared dataUri']);
                $decoded = urldecode(string: $logo);
                if (str_starts_with(haystack: $decoded, needle: 'data:image/')) {
                    if (str_contains(haystack: $decoded, needle: ';base64,') && strlen(string: $decoded) < 12000) {
                        $dataUri = $this->canonicalizeDataUri(dataUri: $decoded);
                        $this->debugLog(event: 'applyLogo:fallback-data-uri', context: [
                            'decoded_length' => strlen(string: $decoded),
                            'canonical_length' => strlen(string: $dataUri),
                        ]);
                        $width = 14;
                        $height = 14;
                        return $this->embedLogoInSvg(svg: $svg, logoDataUri: $dataUri, mime: 'png', width: $width, height: $height);
                    }
                }
                if (! str_starts_with(haystack: $logo, needle: 'data:')) {
                    $rawNorm = LogoDataHelper::normalizeRawBase64(input: $logo);
                    if ($rawNorm !== null) {
                        $bin = base64_decode(string: $rawNorm, strict: true);
                        if ($bin !== false && $bin !== '') {
                            $mime = LogoDataHelper::inferMime(binary: $bin) ?? '';
                            if ($mime !== '') {
                                $maxBytes = $this->safeConfig(key: 'badge.logo_max_bytes', default: 10000);
                                if (LogoDataHelper::withinSize(binary: $bin, maxBytes: $maxBytes)) {
                                    if ($mime === 'svg+xml') {
                                        $san = LogoDataHelper::sanitizeSvg(svg: $bin);
                                        if ($san === null) {
                                            $this->debugLog(event: 'applyLogo:raw-base64-sanitization-failed');
                                            return $svg;
                                        }
                                        $bin = $san;
                                    }
                                    $dataUri = $this->canonicalizeDataUri(dataUri: 'data:image/' . $mime . ';base64,' . base64_encode(string: $bin));
                                    $this->debugLog(event: 'applyLogo:raw-base64-success', context: [
                                        'mime' => $mime,
                                        'data_uri_length' => strlen(string: $dataUri),
                                    ]);
                                    $width = 14;
                                    $height = 14;
                                    return $this->embedLogoInSvg(svg: $svg, logoDataUri: $dataUri, mime: $mime, width: $width, height: $height);
                                }
                            }
                        }
                    } else {
                        $this->debugLog(event: 'applyLogo:raw-base64-normalization-failed');
                    }
                }
                return $svg; // degrade silently
            }
            if (isset($prepared['binary'])) {
                $maxBytes = $this->safeConfig(key: 'badge.logo_max_bytes', default: 10000);
                $binLen = strlen(string: $prepared['binary']);
                if ($binLen > $maxBytes) {
                    $this->debugLog(event: 'applyLogo:prepared-too-large', context: ['bytes' => $binLen, 'max' => $maxBytes]);
                    return $svg;
                }
            }
            $dataUri = $this->canonicalizeDataUri(dataUri: $prepared['dataUri']);
            $width = (int) $prepared['width'];
            $height = (int) $prepared['height'];
            $mime = (string) $prepared['mime'];
            $this->debugLog(event: 'applyLogo:prepared-success', context: [
                'mime' => $mime,
                'width' => $width,
                'height' => $height,
                'data_uri_length' => strlen(string: $dataUri),
            ]);
            if ($mime !== 'svg+xml' && isset($prepared['binary'])) {
                $dataUri = $this->canonicalizeDataUri(dataUri: 'data:image/' . $mime . ';base64,' . base64_encode(string: $prepared['binary']));
                $this->debugLog(event: 'applyLogo:raster-canonicalized', context: ['new_length' => strlen(string: $dataUri)]);
            }
            if ($logoColor === 'auto' && $mime === 'svg+xml') {
                $logoColor = $this->deriveAutoLogoColor(svg: $svg, providedLabelColor: $labelColor, messageBackgroundFill: $messageBackgroundFill);
                $this->debugLog(event: 'applyLogo:logoColor-auto-derived', context: ['derived' => $logoColor]);
            }
            if ($mime === 'svg+xml' && $logoColor === null && ! str_starts_with(haystack: $logo, needle: 'data:')) {
                $logoColor = 'f5f5f5';
            }
            if ($logoColor && $mime === 'svg+xml') {
                $decodedSvg = null;
                if (isset($prepared['binary'])) {
                    $decodedSvg = $prepared['binary'];
                } elseif (preg_match(pattern: '#^data:image/svg\+xml;base64,([A-Za-z0-9+/=]+)$#', subject: $dataUri, matches: $m)) {
                    $decodedSvg = base64_decode(string: $m[1], strict: true) ?: null;
                }
                if ($decodedSvg) {
                    $hex = $this->getHexColor(color: $logoColor);
                    $recolored = $this->recolorSvg(svg: $decodedSvg, hex: $hex);
                    if ($recolored !== null) {
                        $dataUri = $this->canonicalizeDataUri(dataUri: 'data:image/svg+xml;base64,' . base64_encode(string: $recolored));
                        $this->debugLog(event: 'applyLogo:recolor-success', context: ['hex' => $hex]);
                    }
                }
            }
            $svg = $this->embedLogoInSvg(svg: $svg, logoDataUri: $dataUri, mime: $mime, width: $width, height: $height);
            $this->debugLog(event: 'applyLogo:embed-complete');
        } catch (\Throwable $e) {
            $this->debugLog(event: 'applyLogo:exception', context: ['msg' => $e->getMessage()]);
            $decoded = urldecode(string: $logo);
            if (str_starts_with(haystack: $decoded, needle: 'data:image/') && str_contains(haystack: $decoded, needle: ';base64,') && strlen(string: $decoded) < 16000) {
                $dataUri = $this->canonicalizeDataUri(dataUri: $decoded);
                $width = 14;
                $height = 14;
                if (! str_contains(haystack: $svg, needle: '<image')) {
                    $fallbackImage = '<image x="4" y="2" width="' . $width . '" height="' . $height . '" href="' . $dataUri . '" />';
                    if (str_contains(haystack: $svg, needle: '</svg>')) {
                        $svg = str_replace(search: '</svg>', replace: $fallbackImage . '</svg>', subject: $svg);
                    } else {
                        $svg .= $fallbackImage;
                    }
                    $this->debugLog(event: 'applyLogo:salvage-success');
                }
            }
        }
        if ($dataUri && strpos(haystack: $svg, needle: '<image') === false) {
            $useMime = $mime !== '' ? $mime : 'png';
            $svg = $this->embedLogoInSvg(svg: $svg, logoDataUri: $dataUri, mime: $useMime, width: $width ?: 14, height: $height ?: 14);
            $this->debugLog(event: 'applyLogo:post-fallback-embed');
        }
        return $svg;
    }

    private function canonicalizeDataUri(string $dataUri): string
    {
        if (! str_starts_with(haystack: $dataUri, needle: 'data:image/') || ! str_contains(haystack: $dataUri, needle: ';base64,')) {
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
                $this->debugLog(event: 'canonicalize:invalid-alphabet', context: [
                    'original_len' => strlen(string: $payload),
                    'sanitized_len' => strlen(string: $sanitized),
                ]);
            }

            return $prefix . ';base64,' . $sanitized;
        }

        $bin = base64_decode($sanitized, true);
        if ($bin === false || $bin === '') {
            if ($mutated) {
                $this->debugLog(event: 'canonicalize:decode-failed');
            }

            return $prefix . ';base64,' . $sanitized;
        }

        $reencoded = base64_encode($bin);
        if ($mutated || $reencoded !== $sanitized) {
            $this->debugLog(event: 'canonicalize:reencoded', context: [
                'original_len' => strlen(string: $payload),
                'sanitized_len' => strlen(string: $sanitized),
                'reencoded_len' => strlen(string: $reencoded),
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
            $hex = $this->getHexColor(color: $providedLabelColor);
        } elseif (preg_match(pattern: '/<rect[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', subject: $svg, matches: $m)) {
            $hex = strtolower(string: $m[1]);
        }
        if ($hex === null && $messageBackgroundFill) {
            $hex = $this->normalizeColorToHex(color: $messageBackgroundFill);
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
        $color = ltrim(string: $color, characters: '#');
        if (preg_match(pattern: '/^[0-9a-fA-F]{6}$/', subject: $color)) {
            return strtolower(string: $color);
        }
        return $this->getHexColor(color: $color);
    }

    /** @return array{0:int,1:int,2:int} */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim(string: $hex, characters: '#');
        if (strlen(string: $hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $int = (int) hexdec(hex_string: substr(string: $hex, offset: 0, length: 2));
        $int2 = (int) hexdec(hex_string: substr(string: $hex, offset: 2, length: 2));
        $int3 = (int) hexdec(hex_string: substr(string: $hex, offset: 4, length: 2));
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

    /**
     * @param string $svg
     * @param string $logoDataUri
     * @param string $mime
     * @param int $width
     * @param int $height
     * @return string
     */
    private function embedLogoInSvg(string $svg, string $logoDataUri, string $mime, int $width = 16, int $height = 16): string
    {
        $this->debugLog(event: 'embedLogoInSvg:start', context: [
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
        ]);
        $badgeHeight = 20.0;
        if (preg_match(pattern: '/<svg[^>]*height="([0-9.]+)"/i', subject: $svg, matches: $hm)) {
            $badgeHeight = (float) $hm[1];
        }
        // Center logo vertically (after clamping height relative to badge height)
        if ($height > $badgeHeight - 2) {
            $height = (int) max(1, $badgeHeight - 2);
        }
        $y = (int) round(num: ($badgeHeight - $height) / 2);
        $padLeft = 10;
        $padRight = 0;
        $segment = $padLeft + $width + $padRight;

        $parser = new BadgeGeometryParser();
        $parsed = $parser->parse(svg: $svg);
        if ($parsed->success) {
            $this->debugLog(event: 'embedLogoInSvg:parser-success');
            $totalWidth = (float) $parsed->totalWidth; // parser guarantees non-null
            $labelWidth = (float) $parsed->labelWidth;
            $statusWidth = (float) $parsed->statusWidth;
            $statusX = (float) $parsed->statusX;
        } else {
            // Fallback to legacy regex path (retain previous tolerant behavior)
            $this->debugLog(event: 'embedLogoInSvg:parser-fallback', context: ['reason' => $parsed->reason]);
            if (! preg_match(pattern: '/<svg[^>]*width="([0-9.]+)"/i', subject: $svg, matches: $mTotal)) {
                $this->debugLog(event: 'embedLogoInSvg:missing-total-width');
                return $this->simpleInjectLogo(svg: $svg, logoDataUri: $logoDataUri, width: $width, height: $height, y: $y);
            }
            $totalWidth = (float) $mTotal[1];
            $labelMatch = preg_match(pattern: '/<rect[^>]*fill="#555"[^>]*width="([0-9.]+)"[^>]*>/', subject: $svg, matches: $mLabel);
            if ($labelMatch === 0) {
                $labelMatch = preg_match(pattern: '/<rect[^>]*width="([0-9.]+)"[^>]*fill="#555"[^>]*>/', subject: $svg, matches: $mLabel);
            }
            if ($labelMatch === 0) {
                $this->debugLog(event: 'embedLogoInSvg:missing-label-rect');
                return $this->simpleInjectLogo(svg: $svg, logoDataUri: $logoDataUri, width: $width, height: $height, y: $y);
            }
            $labelWidth = (float) $mLabel[1];
            $statusMatch = preg_match(pattern: '/<rect[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*>/', subject: $svg, matches: $mStatus);
            if ($statusMatch === 0) {
                $statusMatch = preg_match(pattern: '/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', subject: $svg, matches: $mStatus);
            }
            if ($statusMatch === 0) {
                $this->debugLog(event: 'embedLogoInSvg:missing-status-rect');
                return $this->simpleInjectLogo(svg: $svg, logoDataUri: $logoDataUri, width: $width, height: $height, y: $y);
            }
            if (preg_match('/^<rect[^>]*fill="#/i', $mStatus[0])) {
                $statusX = (float) $mStatus[2];
                $statusWidth = (float) $mStatus[3];
            } else {
                $statusX = (float) $mStatus[1];
                $statusWidth = (float) $mStatus[2];
            }
            $combined = $labelWidth + $statusWidth;
            $delta = abs(num: $combined - $totalWidth);
            if ($delta > max(0.05, $totalWidth * 0.1)) {
                $this->debugLog(event: 'embedLogoInSvg:width-delta-large', context: [
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
     *
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
                $seg = preg_replace(
                    '/width="' . preg_quote(str: (string) $totalWidth, delimiter: '/') . '"/',
                    'width="' . $newTotal . '"',
                    $seg,
                    1,
                ) ?? $seg;
            }
            return $seg;
        }, $svg, 1) ?? $svg;
        // Adjust background rect(s)
        $svg = preg_replace(
            '/<rect([^>]*?)width="' . preg_quote(str: (string) $totalWidth, delimiter: '/') . '"([^>]*?)height="' . $badgeHeight . '"([^>]*?)fill="#fff"\/>/',
            '<rect$1width="' . $newTotal . '"$2height="' . $badgeHeight . '"$3fill="#fff"/>',
            $svg,
            1,
        ) ?? $svg;
        $svg = preg_replace(
            '/<rect([^>]*?)width="' . preg_quote(str: (string) $totalWidth, delimiter: '/') . '"([^>]*?)height="' . $badgeHeight . '"([^>]*?)fill="url\(#b\)"\/>/',
            '<rect$1width="' . $newTotal . '"$2height="' . $badgeHeight . '"$3fill="url(#b)"/>',
            $svg,
            1,
        ) ?? $svg;
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
            return preg_replace(
                '/width="' . preg_quote(str: $mw[1], delimiter: '/') . '"/',
                'width="' . $newLabelWidth . '"',
                $m[0],
                1,
            ) ?? $m[0];
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
            return preg_replace(
                '/x="' . preg_quote(str: $m[1], delimiter: '/') . '"/',
                'x="' . $newStatusX . '"',
                $m[0],
                1,
            ) ?? $m[0];
        }, $svg, 1) ?? $svg;
        // Shift all text x positions right
        $svg = preg_replace_callback('/<text x="([0-9.]+)" y="([0-9.]+)"([^>]*)>/', function (array $m) use ($segment): string {
            $newX = (float) $m[1] + $segment;
            return '<text x="' . $newX . '" y="' . $m[2] . '"' . $m[3] . '>';
        }, $svg) ?? $svg;
        return $svg;
    }

    /**
     * @param string $svg
     * @param string $logoDataUri
     * @param int $width
     * @param int $height
     * @param int $y
     */
    private function simpleInjectLogo(string $svg, string $logoDataUri, int $width, int $height, int $y): string
    {
        $this->debugLog('simpleInjectLogo', [
            'width' => $width,
            'height' => $height,
        ]);
        $fallback = '<image x="4" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . $logoDataUri . '" />';
        if (str_contains(haystack: $svg, needle: '</svg>')) {
            return str_replace(search: '</svg>', replace: $fallback . '</svg>', subject: $svg);
        }
        return $svg . $fallback;
    }

    /**
     * Internal structured debug logging gated by env/config.
     * @param string $event
     * @param array<string,mixed> $context
     */
    private function debugLog(string $event, array $context = []): void
    {
        // Read environment first (runtime unknown to static analyzer) then fallback to config.
        /** @var string|false $envVal */
        $envVal = getenv(name: 'BADGE_DEBUG_LOG');
        if ($envVal !== false) {
            $enabledRaw = $envVal; // string form
        } else {
            try {
                $enabledRaw = $this->safeConfig(key: 'badge.debug_logging', default: false); // bool fallback
            } catch (\Throwable $e) {
                return; // cannot resolve
            }
        }
        $enabled = false;
        if (is_bool($enabledRaw)) {
            $enabled = $enabledRaw;
        } elseif (is_string($enabledRaw)) {
            $normalized = strtolower(string: trim(string: $enabledRaw));
            if ($normalized === '') {
                $enabled = false;
            } elseif (in_array(needle: $normalized, haystack: ['1', 'true', 'yes', 'on'], strict: true)) {
                $enabled = true;
            } elseif (in_array(needle: $normalized, haystack: ['0', 'false', 'no', 'off'], strict: true)) {
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
            if (is_string($v) && strlen(string: $v) > 256) {
                $context[$k] = substr(string: $v, offset: 0, length: 252) . '...';
            }
        }
        try {
            Log::debug(message: '[BadgeRender] ' . $event, context: $context);
        } catch (\Throwable $e) {
            // swallow logging backend issues
        }
    }
}
