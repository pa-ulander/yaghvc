<?php

declare(strict_types=1);

namespace App\Services;

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
     * Prepare logo returning associative array or null if invalid.
     * Returns: [ 'dataUri' => string, 'width' => int, 'height' => int, 'mime' => string ]
     */
    public function prepare(?string $raw, ?string $logoSize = null): ?array
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        // Determine if named slug (no data: prefix)
        if (!str_starts_with($raw, 'data:')) {
            $named = $this->resolveNamedLogo($raw);
            if ($named === null) {
                return null; // Unknown slug
            }
            return $this->sizeSvgDataUri($named['dataUri'], $logoSize, $named['intrinsicWidth'] ?? $this->fixedSize, $named['intrinsicHeight'] ?? $this->fixedSize);
        }

        $dataUri = $this->decodeDataUrlFromQueryParam($raw);
        if ($dataUri === null) {
            return null;
        }

        $parsed = $this->parseDataUri($dataUri);
        if ($parsed === null) {
            return null;
        }

        // For raster formats we cannot easily know intrinsic width/height without decoding binary
        // Leave sizing to outer code; return fixed by default.
        if ($parsed['mime'] !== 'svg+xml') {
            return [
                'dataUri' => $dataUri,
                'width' => $this->fixedSize,
                'height' => $this->fixedSize,
                'mime' => $parsed['mime'],
                'binary' => $parsed['binary'],
            ];
        }

        // SVG: attempt simple width/height extraction (optional)
        [$intrinsicWidth, $intrinsicHeight] = $this->extractSvgDimensions($parsed['binary']);
        return $this->sizeSvgDataUri($dataUri, $logoSize, $intrinsicWidth, $intrinsicHeight, $parsed['binary']);
    }

    /** Convert spaces to plus, strip whitespace, ensure prefix stays. */
    private function decodeDataUrlFromQueryParam(string $value): ?string
    {
        $candidate = urldecode($value);
        $candidate = str_replace(' ', '+', $candidate); // restore plus
        $candidate = preg_replace('/\s+/', '', $candidate) ?? '';
        if (!str_starts_with($candidate, 'data:')) {
            return null;
        }
        return $candidate;
    }

    /** Parse a data URI returning [mime,binary] or null */
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

    /** Simple extraction of width/height from SVG tag if present. */
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
        }
        return [
            'dataUri' => $dataUri,
            'width' => $width,
            'height' => $height,
            'mime' => 'svg+xml',
            'binary' => $binary,
        ];
    }

    /**
     * Stub named logo resolver. Returns null if not recognised.
     * Structure: [ dataUri, intrinsicWidth, intrinsicHeight ]
     * You can extend by integrating a simple-icons provider.
     */
    private function resolveNamedLogo(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        $builtin = [
            // Minimal example icon (GitHub mark simplified)
            'github' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16"><path fill="currentColor" d="M8 .2a8 8 0 0 0-2.53 15.6c.4.07.55-.18.55-.39v-1.34c-2.01.37-2.53-.5-2.69-.96-.09-.23-.48-.96-.82-1.15-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.2 1.87.86 2.33.65.07-.52.28-.86.51-1.06-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82a7.5 7.5 0 0 1 2-.27 7.5 7.5 0 0 1 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48v2.2c0 .21.15.46.55.39A8 8 0 0 0 8 .2Z"/></svg>'
        ];
        if (!isset($builtin[$slug])) {
            return null;
        }
        $svg = $builtin[$slug];
        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        return [
            'dataUri' => $dataUri,
            'intrinsicWidth' => 16,
            'intrinsicHeight' => 16,
        ];
    }
}
