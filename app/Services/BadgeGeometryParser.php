<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Parses Poser-generated badge SVG to extract geometry metrics.
 *
 * @package App\Services
 */
final class BadgeGeometryParser
{
    public const string REASON_NO_TOTAL_WIDTH = 'no_total_width';
    public const string REASON_NO_LABEL_RECT = 'no_label_rect';
    public const string REASON_NO_STATUS_RECT = 'no_status_rect';
    public const string REASON_WIDTH_MISMATCH = 'width_mismatch';

    public function parse(string $svg): BadgeGeometryResult
    {
        if (! preg_match(pattern: '/<svg[^>]*width="([0-9.]+)"/i', subject: $svg, matches: $mTotal)) {
            return BadgeGeometryResult::failure(reason: self::REASON_NO_TOTAL_WIDTH);
        }
        $totalWidth = (float) $mTotal[1];
        $height = 20.0;
        if (preg_match(pattern: '/<svg[^>]*height="([0-9.]+)"/i', subject: $svg, matches: $mH)) {
            $height = (float) $mH[1];
        }
        if (! preg_match(pattern: '/<rect[^>]*fill="#555"[^>]*width="([0-9.]+)"[^>]*>/', subject: $svg, matches: $mLabel) &&
            ! preg_match(pattern: '/<rect[^>]*width="([0-9.]+)"[^>]*fill="#555"[^>]*>/', subject: $svg, matches: $mLabel)
        ) {
            return BadgeGeometryResult::failure(self::REASON_NO_LABEL_RECT);
        }
        $labelWidth = (float) $mLabel[1];

        // Match status rect (two ordering variants). If neither matches, fail early.
        if (preg_match(pattern: '/<rect[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*>/', subject: $svg, matches: $mStatusColorFirst)) {
            $statusX = (float) $mStatusColorFirst[2];
            $statusWidth = (float) $mStatusColorFirst[3];
        } elseif (preg_match(pattern: '/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', subject: $svg, matches: $mStatusXFirst)) {
            $statusX = (float) $mStatusXFirst[1];
            $statusWidth = (float) $mStatusXFirst[2];
        } else {
            return BadgeGeometryResult::failure(reason: self::REASON_NO_STATUS_RECT);
        }

        // Validate combined width within tolerance; statusX & statusWidth are guaranteed set here.
        if (abs(($labelWidth + $statusWidth) - $totalWidth) > 0.05) {
            return BadgeGeometryResult::failure(reason: self::REASON_WIDTH_MISMATCH);
        }

        return BadgeGeometryResult::success(totalWidth: $totalWidth, height: $height, labelWidth: $labelWidth, statusWidth: $statusWidth, statusX: $statusX);
    }
}
