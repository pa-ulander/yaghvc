<?php

declare(strict_types=1);

namespace App\Services;

use App\Factories\LogoProcessorChainFactory;
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

        // Create request value object
        $request = LogoProcessorChainFactory::createRequest(
            raw: $raw,
            logoSize: $logoSize,
            targetHeight: $this->targetHeight,
            fixedSize: $this->fixedSize,
            maxBytes: $this->configInt('badge.logo_max_bytes', 10000),
            maxDimension: $this->configInt('badge.logo_max_dimension', 64),
            cacheTtl: $this->configInt('badge.logo_cache_ttl', 0)
        );

        // Build and execute handler chain
        $chain = LogoProcessorChainFactory::create($request);
        $result = $chain->handle($request);

        if ($result === null) {
            return null;
        }

        // Convert LogoResult to legacy array format
        $array = [
            'dataUri' => $result->dataUri,
            'width' => $result->width,
            'height' => $result->height,
            'mime' => $result->mime,
        ];

        if ($result->binary !== null) {
            $array['binary'] = $result->binary;
        }

        return $array;
    }

    // ========================================================================
    // DEPRECATED: The methods below are kept for backward compatibility with
    // existing tests. New code should use the Chain of Responsibility handlers.
    // ========================================================================

    /**
     * @param array<mixed,mixed> $payload
     * @deprecated Use LogoResult value object instead
     * @phpstan-ignore method.unused
     */
    private function isPreparedPayload(array $payload): bool
    {
        if (! isset($payload['dataUri'], $payload['width'], $payload['height'], $payload['mime'])) {
            return false;
        }
        if (! is_string($payload['dataUri']) || ! is_string($payload['mime'])) {
            return false;
        }
        if (! is_int($payload['width']) || ! is_int($payload['height'])) {
            return false;
        }
        if (isset($payload['binary']) && ! is_string($payload['binary'])) {
            return false;
        }
        return true;
    }

    private function configInt(string $key, int $default): int
    {
        if (! function_exists('config')) {
            return $default;
        }
        try {
            $value = config($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        return $default;
    }

    /**
     * @deprecated Use UrlDecodedLogoHandler instead
     * @phpstan-ignore method.unused
     */
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
     * @deprecated Use RawBase64LogoHandler instead
     * @phpstan-ignore method.unused
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
        $maxBytes = $this->configInt('badge.logo_max_bytes', 10000);
        if (!LogoDataHelper::withinSize($binary, $maxBytes)) {
            return null;
        }
        return 'data:image/' . $mime . ';base64,' . base64_encode($binary);
    }


    /**
     * @deprecated Use DataUriLogoHandler instead
     * @return array{mime:string,binary:string}|null
     * @phpstan-ignore method.unused
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
     * @deprecated Use DataUriLogoHandler instead
     * @return array{0:int,1:int}
     * @phpstan-ignore method.unused
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
     * @deprecated Logic moved to handler chain
     * @return array{dataUri:string,width:int,height:int,mime:string,binary?:string}
     * @phpstan-ignore method.unused
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
            $maxDim = $this->configInt('badge.logo_max_dimension', 64);
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
     * @deprecated Use SlugLogoHandler instead
     * @return array{dataUri:string,intrinsicWidth:int,intrinsicHeight:int}|null
     * @phpstan-ignore method.unused
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
            $patched = preg_replace('/<svg /i', '<svg width="24" height="24" ', $svgContent, 1);
            if (is_string($patched)) {
                $svgContent = $patched;
            }
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
