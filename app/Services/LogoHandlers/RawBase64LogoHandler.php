<?php

declare(strict_types=1);

namespace App\Services\LogoHandlers;

use App\Services\LogoDataHelper;
use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

/**
 * Handler for raw base64 encoded logos.
 *
 * Attempts to interpret raw (possibly URL-encoded) base64 strings as images
 * and wraps them in proper data URIs. Only processes strings that don't
 * already start with "data:".
 */
class RawBase64LogoHandler extends AbstractLogoHandler
{
    /**
     * Handle raw base64 if input doesn't start with "data:".
     */
    protected function canHandle(LogoRequest $request): bool
    {
        return !str_starts_with($request->raw, 'data:');
    }

    /**
     * Normalize raw base64 to data URI or pass to next handler.
     */
    protected function process(LogoRequest $request): ?LogoResult
    {
        $normalized = $this->normalizeLooseBase64($request);

        if ($normalized !== null) {
            // Update the request with normalized data URI and pass to next handler
            $updatedRequest = new LogoRequest(
                raw: $normalized,
                logoSize: $request->logoSize,
                targetHeight: $request->targetHeight,
                fixedSize: $request->fixedSize,
                maxBytes: $request->maxBytes,
                maxDimension: $request->maxDimension,
                cacheTtl: $request->cacheTtl,
            );

            return $this->handleNext($updatedRequest);
        }

        // Not raw base64, pass to next handler unchanged
        return $this->handleNext($request);
    }

    /**
     * Attempt to interpret raw base64 blob as image and wrap in data URI.
     */
    private function normalizeLooseBase64(LogoRequest $request): ?string
    {
        $candidate = LogoDataHelper::normalizeRawBase64($request->raw);
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

        // Sanitize SVG content
        if ($mime === 'svg+xml') {
            $sanitized = LogoDataHelper::sanitizeSvg($binary);
            if ($sanitized === null) {
                return null;
            }
            $binary = $sanitized;
        }

        // Check size limits
        if (!LogoDataHelper::withinSize($binary, $request->maxBytes)) {
            return null;
        }

        return 'data:image/' . $mime . ';base64,' . base64_encode($binary);
    }
}
