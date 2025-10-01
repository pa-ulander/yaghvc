<?php

declare(strict_types=1);

namespace App\Services\LogoHandlers;

use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

/**
 * Handler for simple-icons logo slugs.
 *
 * Resolves simple-icons slug names (e.g., "github", "gitlab") to SVG data URIs
 * by reading from the vendor/simple-icons/simple-icons/icons directory.
 */
class SlugLogoHandler extends AbstractLogoHandler
{
    /**
     * Handle if input looks like a slug (letters/digits/hyphens, 1-60 chars).
     */
    protected function canHandle(LogoRequest $request): bool
    {
        return !str_starts_with($request->raw, 'data:')
            && preg_match('/^[a-z0-9-]{1,60}$/i', $request->raw) === 1;
    }

    /**
     * Resolve slug to SVG data URI with sizing.
     */
    protected function process(LogoRequest $request): ?LogoResult
    {
        $resolved = $this->resolveNamedLogo($request->raw);

        if ($resolved === null) {
            return null; // Unknown slug
        }

        // Calculate dimensions based on logoSize
        $width = $height = $request->fixedSize;

        if ($request->logoSize === 'auto') {
            // Auto sizing: maintain aspect ratio
            $aspect = $resolved['intrinsicHeight'] > 0
                ? $resolved['intrinsicWidth'] / $resolved['intrinsicHeight']
                : 1.0;
            $height = $request->targetHeight;
            $width = (int) round($height * $aspect);
        } elseif ($request->logoSize !== null && ctype_digit($request->logoSize)) {
            // Numeric size
            $size = (int) $request->logoSize;
            $size = max(8, min($request->maxDimension, $size));
            $width = $height = $size;
        }

        return new LogoResult(
            dataUri: $resolved['dataUri'],
            width: $width,
            height: $height,
            mime: 'svg+xml',
            binary: null
        );
    }

    /**
     * Resolve a simple-icons slug to a data URI (24x24 base dimensions).
     *
     * @return array{dataUri:string,intrinsicWidth:int,intrinsicHeight:int}|null
     */
    private function resolveNamedLogo(string $slug): ?array
    {
        $slug = strtolower(trim($slug));

        $basePathFn = function (string $path): string {
            if (function_exists('base_path')) {
                try {
                    return base_path($path);
                } catch (\Throwable $e) {
                    // Fall through to filesystem fallback
                }
            }
            $root = realpath(__DIR__ . '/../../../');
            if (!$root) {
                $root = getcwd();
            }
            return $root . '/' . $path;
        };

        $basePath = $basePathFn('vendor/simple-icons/simple-icons/icons');
        $file = $basePath . '/' . $slug . '.svg';

        if (!is_readable($file)) {
            return null;
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

        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($svgContent);

        return [
            'dataUri' => $dataUri,
            'intrinsicWidth' => 24,
            'intrinsicHeight' => 24,
        ];
    }
}
