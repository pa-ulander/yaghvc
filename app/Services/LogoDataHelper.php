<?php

declare(strict_types=1);

namespace App\Services;

/**
 * LogoDataHelper centralizes common low-level operations used by validation and processing:
 *  - Base64 normalization (handle spaces vs '+', strip whitespace)
 *  - Strict base64 decode & length guard
 *  - MIME inference for inline image binary (png/jpeg/gif/svg)
 *  - SVG safety sanitization (removes dangerous constructs)
 *  - Size limit checks
 *
 * All methods are static & pure (no framework dependencies) to allow usage from FormRequest.
 */
final class LogoDataHelper
{
    /**
     * Normalize a potential raw (optionally urlencoded) base64 string;
     * returns cleaned base64 or null.
     *
     * @param string $input Raw or urlencoded base64 input
     */
    public static function normalizeRawBase64(string $input): ?string
    {
        $decodedOnce = urldecode($input);
        if (str_contains($decodedOnce, ' ')) {
            $decodedOnce = str_replace(' ', '+', $decodedOnce);
        }
        $candidate = preg_replace('/\s+/', '', $decodedOnce) ?? '';
        if ($candidate === '' || !preg_match('/^[A-Za-z0-9+\/]+=*$/', $candidate)) {
            return null;
        }
        return $candidate;
    }

    /** Infer supported mime from binary; returns one of png|jpeg|gif|svg+xml or null. */
    public static function inferMime(string $binary): ?string
    {
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (str_starts_with($binary, "\xFF\xD8")) {
            return 'jpeg';
        }
        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'gif';
        }
        $trim = ltrim($binary, "\xEF\xBB\xBF\r\n\t \0");
        // Common SVG starts directly with <svg ...>
        if (str_starts_with($trim, '<svg')) {
            return 'svg+xml';
        }
        // Allow optional XML declaration before the <svg> element
        if (str_starts_with($trim, '<?xml')) {
            // Find first <svg tag after declaration (robust against whitespace/comments)
            if (preg_match('/<svg\b/i', $trim)) {
                return 'svg+xml';
            }
        }
        return null;
    }

    /**
     * Return sanitized SVG binary or null if unsafe.
     * Rejects <script>, <foreignObject>, event handlers, non-data xlink:href,
     * and ensures it contains an <svg> element.
     *
     * @param string $svg Raw SVG input
     */
    public static function sanitizeSvg(string $svg): ?string
    {
        $lower = strtolower($svg);
        if (str_contains($lower, '<script') || str_contains($lower, '<foreignobject')) {
            return null;
        }
        $svg = preg_replace('/on[a-z]+="[^"]*"/i', '', $svg) ?? $svg;
        $svg = preg_replace('/xlink:href="(?!data:)[^"]*"/i', 'xlink:href=""', $svg) ?? $svg;
        if (preg_match('/href="javascript:/i', $svg)) {
            return null;
        }
        if (!preg_match('/<svg[^>]*>/i', $svg)) {
            return null;
        }
        return $svg;
    }

    /**
     * Ensure size within limit (decoded bytes).
     *
     * @param string $binary Raw binary bytes of the logo image (not base64; direct image data).
     */
    public static function withinSize(string $binary, int $maxBytes): bool
    {
        return strlen($binary) <= $maxBytes;
    }
}
