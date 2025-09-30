<?php

declare(strict_types=1);

namespace App\Services\LogoHandlers;

use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

/**
 * Handler for percent-encoded data URIs.
 *
 * Detects and decodes percent-encoded data URIs (e.g., data%3Aimage%2F...)
 * that often result from URL encoding in query parameters.
 */
class UrlDecodedLogoHandler extends AbstractLogoHandler
{
    /**
     * Handle if input appears to be percent-encoded data URI.
     */
    protected function canHandle(LogoRequest $request): bool
    {
        return preg_match('/^data%3Aimage%2F/i', $request->raw) === 1;
    }

    /**
     * Decode percent-encoded data URI and pass to next handler.
     */
    protected function process(LogoRequest $request): ?LogoResult
    {
        $decoded = urldecode($request->raw);

        if (str_starts_with($decoded, 'data:image/')) {
            // Update request with decoded URI
            $updatedRequest = new LogoRequest(
                raw: $decoded,
                logoSize: $request->logoSize,
                targetHeight: $request->targetHeight,
                fixedSize: $request->fixedSize,
                maxBytes: $request->maxBytes,
                maxDimension: $request->maxDimension,
                cacheTtl: $request->cacheTtl,
            );

            return $this->handleNext($updatedRequest);
        }

        // Decoding failed or invalid, pass unchanged
        return $this->handleNext($request);
    }
}
