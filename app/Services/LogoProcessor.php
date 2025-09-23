<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * LogoProcessor inspired by shields.io logo handling.
 * Responsibilities:
 *  - Normalise user supplied logo parameter (either data URI or named slug)
 *  - Decode & validate data URI (base64 image/svg)
 *  - Provide adaptive sizing (auto) based on intrinsic aspect ratio
 *  - (Stub) Resolve named logos – can be extended to integrate a simple-icons provider later
 */
class LogoProcessor
{
    /** Default target height inside badge */
    private int $targetHeight;

    /** Fixed size (square) used when not auto */
    private int $fixedSize;

    public function __construct(int $targetHeight = 14, int $fixedSize = 14)
    {
        $this->targetHeight = $targetHeight;
        $this->fixedSize = $fixedSize;
    }

    /**
     * Prepare logo returning structured array or null if invalid.
     * Shape differs for svg vs raster (binary always present for parsed forms).
     * @return array{dataUri:string,width:int,height:int,mime:string,binary?:string}|null
     */
    public function prepare(?string $raw, ?string $logoSize = null): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        // Step 1: Attempt to interpret any non data: string as raw base64 BEFORE slug resolution.
        // This prevents misclassification when a base64 payload accidentally matches future slug patterns.
        if (!str_starts_with($raw, 'data:')) {
            $maybe = $this->normaliseLooseBase64($raw);
            if ($maybe !== null) {
                $raw = $maybe; // Promote to data URI path.
            }
        }

        // If the logo appears to be a percent-encoded data URI, decode it once so later logic
        // recognises it as a data: URL. Users are instructed to percent-encode the entire value,
        // so handling the encoded prefix here improves robustness.
        if (preg_match('/^data%3Aimage%2F/i', $raw) === 1) {
            $decodedOnce = urldecode($raw);
            if (str_starts_with($decodedOnce, 'data:image/')) {
                $raw = $decodedOnce;
            }
        }

        $cacheTtl = 0;
        if (function_exists('config')) {
            try {
                $cacheTtl = (int) config('badge.logo_cache_ttl', 0);
            } catch (\Throwable $e) {
                $cacheTtl = 0; // container not ready
            }
        }
        $cacheKey = null;
        if ($cacheTtl > 0 && class_exists(Cache::class)) {
            $cacheKey = 'logo:' . sha1($raw . '|' . ($logoSize ?? ''));
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        // Determine if named slug (only when clearly slug-shaped: letters/digits hyphen only)
        if (!str_starts_with($raw, 'data:') && preg_match('/^[a-z0-9-]{1,60}$/i', $raw)) {
            $named = $this->resolveNamedLogo($raw);
            if ($named === null) {
                return null; // Unknown slug
            }
            $result = $this->sizeSvgDataUri($named['dataUri'], $logoSize, $named['intrinsicWidth'], $named['intrinsicHeight']);
            if ($cacheKey && $cacheTtl > 0) {
                $cachePayload = $result;
                unset($cachePayload['binary']);
                Cache::put($cacheKey, $cachePayload, $cacheTtl);
            }
            return $result;
        }

        $dataUri = $this->decodeDataUrlFromQueryParam($raw);
        // (debug removed)
        if ($dataUri === null) {
            return null;
        }

        $parsed = $this->parseDataUri($dataUri);
        // (debug removed)
        if ($parsed === null) {
            // Salvage attempt: tolerate uncommon base64 variants (e.g., urlencoded edge cases)
            if (str_starts_with($dataUri, 'data:image/')) {
                $semi = strpos($dataUri, ';base64,');
                if ($semi !== false) {
                    $prefix = substr($dataUri, 0, $semi);
                    $mime = substr($prefix, strlen('data:image/'));
                    $b64 = substr($dataUri, $semi + 8);
                    // Remove any stray whitespace
                    $b64 = preg_replace('/\s+/', '', $b64) ?? $b64;
                    $bin = base64_decode($b64, true);
                    if ($bin !== false && $bin !== '') {
                        $parsedMime = preg_replace('/[^a-z0-9+]+/i', '', $mime);
                        $parsed = [
                            'mime' => $parsedMime === 'svg+xml' ? 'svg+xml' : ($parsedMime !== '' ? $parsedMime : 'png'),
                            'binary' => $bin,
                        ];
                    }
                }
            }
            if ($parsed === null) {
                return null;
            }
        }

        // For raster formats we cannot easily know intrinsic width/height without decoding binary
        // Leave sizing to outer code; return fixed by default.
        if ($parsed['mime'] !== 'svg+xml') {
            // Enforce byte size limit
            $maxBytes = 10000;
            if (function_exists('config')) {
                try {
                    $maxBytes = (int) config('badge.logo_max_bytes', 10000);
                } catch (\Throwable $e) {
                }
            }
            if (strlen($parsed['binary']) > $maxBytes) {
                return null;
            }
            $width = $this->fixedSize;
            $height = $this->fixedSize;
            // Try to detect intrinsic size & enforce max dimensions
            if (function_exists('getimagesizefromstring')) {
                $info = @getimagesizefromstring($parsed['binary']);
                if (is_array($info)) {
                    // indexes 0 and 1 always exist for valid image size arrays
                    $intrinsicW = (int) $info[0];
                    $intrinsicH = (int) $info[1];
                    if ($intrinsicW > 0 && $intrinsicH > 0) {
                        $maxDim = 64;
                        if (function_exists('config')) {
                            try {
                                $maxDim = (int) config('badge.logo_max_dimension', 64);
                            } catch (\Throwable $e) {
                            }
                        }
                        if ($intrinsicW > $maxDim || $intrinsicH > $maxDim) {
                            return null; // reject oversize raster
                        }
                    }
                }
            }
            if ($logoSize && $logoSize !== 'auto' && ctype_digit($logoSize)) {
                $v = (int) $logoSize;
                $maxDim = 64;
                if (function_exists('config')) {
                    try {
                        $maxDim = (int) config('badge.logo_max_dimension', 64);
                    } catch (\Throwable $e) {
                    }
                }
                $v = max(8, min($maxDim, $v));
                $width = $height = $v;
            }
            $result = [
                'dataUri' => $dataUri,
                'width' => $width,
                'height' => $height,
                'mime' => $parsed['mime'],
                'binary' => $parsed['binary'],
            ];
            if ($cacheKey && $cacheTtl > 0) {
                $cachePayload = $result;
                unset($cachePayload['binary']);
                Cache::put($cacheKey, $cachePayload, $cacheTtl);
            }
            return $result;
        }

        // SVG: attempt simple width/height extraction (optional)
        [$intrinsicWidth, $intrinsicHeight] = $this->extractSvgDimensions($parsed['binary']);
        $result = $this->sizeSvgDataUri($dataUri, $logoSize, $intrinsicWidth, $intrinsicHeight, $parsed['binary']);
        if ($cacheKey && $cacheTtl > 0) {
            $cachePayload = $result;
            unset($cachePayload['binary']);
            Cache::put($cacheKey, $cachePayload, $cacheTtl);
        }
        return $result;
    }

    /** Convert spaces to plus, strip whitespace, ensure prefix stays. */
    private function decodeDataUrlFromQueryParam(string $value): ?string
    {
        // Original logic: urldecode then convert spaces back to plus; compress whitespace
        $candidate = urldecode($value);
        $candidate = str_replace(' ', '+', $candidate);
        $candidate = preg_replace('/\s+/', '', $candidate) ?? '';
        if (!str_starts_with($candidate, 'data:')) {
            return null;
        }
        return $candidate;
    }

    /**
     * Attempt to interpret a raw (maybe URL-encoded) base64 blob as an image and wrap in a data URI.
     * Returns normalised data URI string or null if not base64 or undecidable.
     */
    private function normaliseLooseBase64(string $input): ?string
    {
        $candidate = LogoDataHelper::normalizeRawBase64($input);
        if ($candidate === null) {
            return null;
        }
        $binary = base64_decode($candidate, true);
        if ($binary === false || $binary === '') {
            return null;
        }
        $mime = LogoDataHelper::inferMime($binary);
        if ($mime === null) {
            return null;
        }
        if ($mime === 'svg+xml') {
            $sanitized = LogoDataHelper::sanitizeSvg($binary);
            if ($sanitized === null) {
                return null;
            }
            $binary = $sanitized;
        }
        $maxBytes = 10000;
        if (function_exists('config')) {
            try {
                $maxBytes = (int) config('badge.logo_max_bytes', 10000);
            } catch (\Throwable $e) {
            }
        }
        if (!LogoDataHelper::withinSize($binary, $maxBytes)) {
            return null;
        }
        return 'data:image/' . $mime . ';base64,' . base64_encode($binary);
    }


    /**
     * Parse a data URI returning its mime and binary content.
     * @return array{mime:string,binary:string}|null
     */
    private function parseDataUri(string $dataUri): ?array
    {
        if (!preg_match('#^data:image/(png|jpeg|jpg|gif|svg\+xml);base64,([A-Za-z0-9+/=]+)$#', $dataUri, $m)) {
            return null;
        }
        $binary = base64_decode($m[2], true);
        if ($binary === false || $binary === '') {
            return null;
        }
        return ['mime' => $m[1], 'binary' => $binary];
    }

    /**
     * Simple extraction of width/height from SVG tag if present.
     * @return array{0:int,1:int}
     */
    private function extractSvgDimensions(string $svg): array
    {
        $w = $h = $this->fixedSize;
        if (preg_match('/<svg[^>]*width="([0-9.]+)"/i', $svg, $wm)) {
            $w = (int) ceil((float) $wm[1]);
        }
        if (preg_match('/<svg[^>]*height="([0-9.]+)"/i', $svg, $hm)) {
            $h = (int) ceil((float) $hm[1]);
        }
        // Try viewBox fallback
        if (($w === $this->fixedSize || $h === $this->fixedSize) && preg_match('/viewBox="0 0 ([0-9.]+) ([0-9.]+)"/i', $svg, $vb)) {
            $w = (int) ceil((float) $vb[1]);
            $h = (int) ceil((float) $vb[2]);
        }
        if ($w <= 0) {
            $w = $this->fixedSize;
        }
        if ($h <= 0) {
            $h = $this->fixedSize;
        }
        return [$w, $h];
    }

    /**
     * Return sized svg data URI info (always mime svg+xml).
     * @return array{dataUri:string,width:int,height:int,mime:string,binary?:string}
     */
    private function sizeSvgDataUri(string $dataUri, ?string $logoSize, int $intrinsicWidth, int $intrinsicHeight, ?string $binary = null): array
    {
        $height = $this->fixedSize;
        $width = $this->fixedSize;
        if ($logoSize === 'auto') {
            // Scale width proportionally to target height
            $ratio = $intrinsicWidth / max(1, $intrinsicHeight);
            $height = $this->targetHeight;
            $width = (int) round($height * $ratio);
            $width = max(1, min(2 * $this->targetHeight, $width)); // clamp
        } elseif ($logoSize && ctype_digit($logoSize)) {
            $v = (int) $logoSize;
            $maxDim = 64;
            if (function_exists('config')) {
                try {
                    $maxDim = (int) config('badge.logo_max_dimension', 64);
                } catch (\Throwable $e) {
                }
            }
            $v = max(8, min($maxDim, $v));
            $width = $height = $v; // square sizing
        }
        $result = [
            'dataUri' => $dataUri,
            'width' => $width,
            'height' => $height,
            'mime' => 'svg+xml',
        ];
        if ($binary !== null) {
            $result['binary'] = $binary; // include only when non-null
        }
        return $result;
    }

    /**
     * Resolve a simple-icons slug to a sized data URI (24x24 base dimensions).
     * @return array{dataUri:string,intrinsicWidth:int,intrinsicHeight:int}|null
     */
    private function resolveNamedLogo(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        $basePathFn = function (string $path) {
            if (function_exists('base_path')) {
                try {
                    return base_path($path);
                } catch (\Throwable $e) {
                }
            }
            $root = realpath(__DIR__ . '/../../'); // app/Services -> project/app -> project
            if (!$root) {
                $root = getcwd();
            }
            return $root . '/' . $path;
        };
        $basePath = $basePathFn('vendor/simple-icons/simple-icons/icons');
        $file = $basePath . '/' . $slug . '.svg';
        if (!is_readable($file)) {
            return null; // debug path: $file
        }
        $svgContent = file_get_contents($file);
        if ($svgContent === false) {
            return null;
        }
        // Ensure width/height attributes for sizing (icons usually provide viewBox only)
        if (!preg_match('/width="/i', $svgContent)) {
            $svgContent = preg_replace('/<svg /i', '<svg width="24" height="24" ', $svgContent, 1);
        }
        $svg = $svgContent;
        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        return [
            'dataUri' => $dataUri,
            'intrinsicWidth' => 24,
            'intrinsicHeight' => 24,
        ];
    }
}
