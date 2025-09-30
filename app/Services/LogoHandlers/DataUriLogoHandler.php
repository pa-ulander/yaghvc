<?php

declare(strict_types=1);

namespace App\Services\LogoHandlers;

use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

/**
 * Handler for data URI parsing and validation.
 *
 * Parses data URIs (both SVG and raster formats), validates them,
 * and extracts dimensions for sizing.
 */
class DataUriLogoHandler extends AbstractLogoHandler
{
    /**
     * Handle data URIs.
     */
    protected function canHandle(LogoRequest $request): bool
    {
        return str_starts_with($request->raw, 'data:');
    }

    /**
     * Parse and validate data URI, calculate dimensions.
     */
    protected function process(LogoRequest $request): ?LogoResult
    {
        $dataUri = $this->decodeDataUrlFromQueryParam($request->raw);
        if ($dataUri === null) {
            return null;
        }

        $parsed = $this->parseDataUri($dataUri);

        // Try salvage for uncommon base64 variants
        if ($parsed === null) {
            $parsed = $this->salvageDataUri($dataUri);
        }

        if ($parsed === null) {
            return null;
        }

        // Enforce size limits
        if (strlen($parsed['binary']) > $request->maxBytes) {
            return null;
        }

        // Handle raster images
        if ($parsed['mime'] !== 'svg+xml') {
            return $this->processRasterImage($dataUri, $parsed, $request);
        }

        // Handle SVG images
        return $this->processSvgImage($dataUri, $parsed, $request);
    }

    /**
     * Process raster (PNG, JPEG, GIF) images.
     */
    private function processRasterImage(string $dataUri, array $parsed, LogoRequest $request): ?LogoResult
    {
        $width = $height = $request->fixedSize;

        // Try to detect intrinsic size
        if (function_exists('getimagesizefromstring')) {
            $info = @getimagesizefromstring($parsed['binary']);
            if (is_array($info) && isset($info[0], $info[1])) {
                $intrinsicW = (int) $info[0];
                $intrinsicH = (int) $info[1];

                if ($intrinsicW > 0 && $intrinsicH > 0) {
                    // Enforce max dimensions
                    if ($intrinsicW > $request->maxDimension || $intrinsicH > $request->maxDimension) {
                        return null;
                    }
                }
            }
        }

        // Apply logoSize if specified
        if ($request->logoSize !== null && $request->logoSize !== 'auto' && ctype_digit($request->logoSize)) {
            $size = (int) $request->logoSize;
            $size = max(8, min($request->maxDimension, $size));
            $width = $height = $size;
        }

        return new LogoResult(
            dataUri: $dataUri,
            width: $width,
            height: $height,
            mime: $parsed['mime'],
            binary: $parsed['binary']
        );
    }

    /**
     * Process SVG images with dimension extraction.
     */
    private function processSvgImage(string $dataUri, array $parsed, LogoRequest $request): ?LogoResult
    {
        $svg = $parsed['binary'];
        [$intrinsicW, $intrinsicH] = $this->extractSvgDimensions($svg, $request->fixedSize);

        $width = $height = $request->fixedSize;

        if ($request->logoSize === 'auto') {
            // Auto sizing: maintain aspect ratio
            $aspect = $intrinsicH > 0 ? $intrinsicW / $intrinsicH : 1.0;
            $height = $request->targetHeight;
            $width = (int) round($height * $aspect);
        } elseif ($request->logoSize !== null && ctype_digit($request->logoSize)) {
            // Numeric size
            $size = (int) $request->logoSize;
            $size = max(8, min($request->maxDimension, $size));
            $width = $height = $size;
        }

        return new LogoResult(
            dataUri: $dataUri,
            width: $width,
            height: $height,
            mime: 'svg+xml',
            binary: $svg
        );
    }

    /**
     * Convert spaces to plus, strip whitespace, ensure data: prefix.
     */
    private function decodeDataUrlFromQueryParam(string $value): ?string
    {
        $candidate = urldecode($value);
        $candidate = str_replace(' ', '+', $candidate);
        $candidate = preg_replace('/\s+/', '', $candidate) ?? '';

        if (!str_starts_with($candidate, 'data:')) {
            return null;
        }

        return $candidate;
    }

    /**
     * Parse a data URI returning its mime and binary content.
     *
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
     * Salvage attempt for uncommon base64 variants.
     *
     * @return array{mime:string,binary:string}|null
     */
    private function salvageDataUri(string $dataUri): ?array
    {
        if (!str_starts_with($dataUri, 'data:image/')) {
            return null;
        }

        $semi = strpos($dataUri, ';base64,');
        if ($semi === false) {
            return null;
        }

        $prefix = substr($dataUri, 0, $semi);
        $mime = substr($prefix, strlen('data:image/'));
        $b64 = substr($dataUri, $semi + 8);

        // Remove any stray whitespace
        $b64 = preg_replace('/\s+/', '', $b64) ?? $b64;
        $bin = base64_decode($b64, true);

        if ($bin === false || $bin === '') {
            return null;
        }

        $parsedMime = preg_replace('/[^a-z0-9+]+/i', '', $mime) ?? '';
        $parsedMime = $parsedMime === 'svg+xml' ? 'svg+xml' : ($parsedMime !== '' ? $parsedMime : 'png');

        return [
            'mime' => $parsedMime,
            'binary' => $bin,
        ];
    }

    /**
     * Extract width/height from SVG tag if present.
     *
     * @return array{0:int,1:int}
     */
    private function extractSvgDimensions(string $svg, int $defaultSize): array
    {
        $w = $h = $defaultSize;

        if (preg_match('/<svg[^>]*width="([0-9.]+)"/i', $svg, $wm)) {
            $w = (int) ceil((float) $wm[1]);
        }
        if (preg_match('/<svg[^>]*height="([0-9.]+)"/i', $svg, $hm)) {
            $h = (int) ceil((float) $hm[1]);
        }

        // Try viewBox fallback
        if (($w === $defaultSize || $h === $defaultSize)
            && preg_match('/viewBox="0 0 ([0-9.]+) ([0-9.]+)"/i', $svg, $vb)
        ) {
            $w = (int) ceil((float) $vb[1]);
            $h = (int) ceil((float) $vb[2]);
        }

        if ($w <= 0) {
            $w = $defaultSize;
        }
        if ($h <= 0) {
            $h = $defaultSize;
        }

        return [$w, $h];
    }
}
