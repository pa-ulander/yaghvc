<?php

declare(strict_types=1);

namespace App\Services;

use PUGX\Poser\Badge;
use PUGX\Poser\Calculator\SvgTextSizeCalculator;
use PUGX\Poser\Poser;
use PUGX\Poser\Render\SvgFlatRender;
use PUGX\Poser\Render\SvgFlatSquareRender;
use PUGX\Poser\Render\SvgForTheBadgeRenderer;
use PUGX\Poser\Render\SvgPlasticRender;

/** @package App\Services */
class BadgeRenderService
{
    private Poser $poser;

    private static array $abbreviations = ['', 'K', 'M', 'B', 'T', 'Qa', 'Qi'];

    public function __construct()
    {
        $this->poser = new Poser([
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

        if ($labelColor) {
            $svg = $this->applyLabelColor($svg, $labelColor);
        }

        if ($logo) {
            $svg = $this->applyLogo($svg, $logo, $logoSize);
        }

        return $svg;
    }

    private function formatNumber(
        int $number,
        bool $abbreviated,
    ): string {
        if ($abbreviated) {
            return $this->formatAbbreviatedNumber(number: $number);
        }

        $reversedString = strrev(string: strval(value: $number));
        $formattedNumber = implode(separator: ',', array: str_split(string: $reversedString, length: 3));

        return strrev(string: $formattedNumber);
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
        $hexColor = $this->getHexColor($labelColor);
        $pattern = '/(<rect[^>]*)(fill="[^"]*")([^>]*>)/';
        $replacement = '$1fill="#' . $hexColor . '"$3';

        return preg_replace($pattern, $replacement, $svg, 1);
    }

    private function applyLogo(string $svg, string $logo, ?string $logoSize = null): string
    {
        try {
            $processor = new LogoProcessor();
            $prepared = $processor->prepare($logo, $logoSize);

            if ($prepared === null) {
                return $svg . '<!--logo-prepared-null-->';
            }
            if (!$prepared) {
                return $svg;
            }
            if (!isset($prepared['dataUri'])) {
                return $svg; // safety
            }
            // Basic limits (byte size + dimension) for raster; SVG intrinsic already constrained in processor
            if (isset($prepared['binary'])) {
                $maxBytes = (int) config('badge.logo_max_bytes', 10000);
                if (strlen($prepared['binary']) > $maxBytes) {
                    return $svg; // reject oversize
                }
            }
            $dataUri = $prepared['dataUri'];
            $width = $prepared['width'];
            $height = $prepared['height'];
            return $this->embedLogoInSvg($svg, $dataUri, $prepared['mime'], $width, $height);
        } catch (\Exception $e) {
            return $svg;
        }
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

        return $colorMap[strtolower($color)] ?? '007ec6'; // Default to blue
    }


    private function embedLogoInSvg(string $svg, string $logoDataUri, string $mime, int $width = 16, int $height = 16): string
    {
        // 1. Determine badge base metrics
        $badgeHeight = 20;
        if (preg_match('/<svg[^>]*height="([0-9.]+)"/i', $svg, $hm)) {
            $badgeHeight = (float) $hm[1];
        }
        if ($height > $badgeHeight - 2) {
            $height = (int) max(1, $badgeHeight - 2);
        }
        $y = (int) round(($badgeHeight - $height) / 2);

        $padLeft = 10;   // space from left edge to logo
        $padRight = 0;  // space between logo and label text
        $segment = $padLeft + $width + $padRight; // horizontal space we must add to the label area

        // 2. Extract width & rect metrics from original badge BEFORE modifications
        if (!preg_match('/<svg[^>]*width="([0-9.]+)"/i', $svg, $mTotal)) {
            return $svg; // unexpected – bail out without breaking badge
        }
        $totalWidth = (float) $mTotal[1];

        if (!preg_match('/<rect[^>]*width="([0-9.]+)"[^>]*fill="#555"[^>]*>/', $svg, $mLabel)) {
            return $svg; // bail if cannot find label rect
        }
        $labelWidth = (float) $mLabel[1];

        // Match status rect: has x attribute and a solid color fill (e.g. #97ca00)
        if (!preg_match('/<rect[^>]*x="([0-9.]+)"[^>]*width="([0-9.]+)"[^>]*fill="#([0-9a-fA-F]{3,8})"[^>]*>/', $svg, $mStatus)) {
            return $svg; // bail if cannot find status rect
        }
        $statusX = (float) $mStatus[1];
        $statusWidth = (float) $mStatus[2];

        // Sanity: ensure pieces line up to total width
        if (abs(($labelWidth + $statusWidth) - $totalWidth) > 0.05) {
            return $svg; // unexpected layout; avoid corrupting svg
        }

        // 3. Compute new geometry
        $newTotal = $totalWidth + $segment;
        $newLabelWidth = $labelWidth + $segment;
        $newStatusX = $statusX + $segment;

        // 4. Apply geometry updates using targeted patterns
        // svg width (full attribute)
        $svg = preg_replace('/width="' . preg_quote((string)$totalWidth, '/') . '"(\s+height="[0-9.]+")/', 'width="' . $newTotal . '"$1', $svg, 1) ?? $svg;
        // mask rect width (line containing rx and fill #fff)
        $svg = preg_replace('/<rect width="' . preg_quote((string)$totalWidth, '/') . '" height="' . $badgeHeight . '" rx="3" fill="#fff"\/>/', '<rect width="' . $newTotal . '" height="' . $badgeHeight . '" rx="3" fill="#fff"/>', $svg, 1) ?? $svg;
        // gradient overlay rect width (fill url(#b))
        $svg = preg_replace('/<rect width="' . preg_quote((string)$totalWidth, '/') . '" height="' . $badgeHeight . '" fill="url\(#b\)"\/>/', '<rect width="' . $newTotal . '" height="' . $badgeHeight . '" fill="url(#b)"/>', $svg, 1) ?? $svg;
        // label rect width (no x, fill #555)
        $labelPattern = '/<rect width="([0-9.]+)" height="' . $badgeHeight . '" fill="#555"\/>/';
        $svg = preg_replace_callback($labelPattern, function ($m) use ($labelWidth, $newLabelWidth) {
            if ((float)$m[1] !== $labelWidth) return $m[0];
            return str_replace('width="' . $m[1] . '"', 'width="' . $newLabelWidth . '"', $m[0]);
        }, $svg, 1) ?? $svg;
        // status rect x update
        $statusPattern = '/<rect x="([0-9.]+)" width="([0-9.]+)" height="' . $badgeHeight . '" fill="#([0-9a-fA-F]{3,8})"\/>/';
        $svg = preg_replace_callback($statusPattern, function ($m) use ($statusX, $statusWidth, $newStatusX) {
            if ((float)$m[1] !== $statusX || (float)$m[2] !== $statusWidth) return $m[0];
            $out = $m[0];
            $out = preg_replace('/x="' . preg_quote($m[1], '\/') . '"/', 'x="' . $newStatusX . '"', $out, 1);
            return $out;
        }, $svg, 1) ?? $svg;

        // 5. Shift text x positions by segment
        $svg = preg_replace_callback('/<text x="([0-9.]+)" y="([0-9.]+)"([^>]*)>/', function ($m) use ($segment) {
            $newX = (float) $m[1] + $segment;
            return '<text x="' . $newX . '" y="' . $m[2] . '"' . $m[3] . '>';
        }, $svg) ?? $svg;

        // 6. Insert logo element inside masked group before first rect (so logo appears "on top" of label background but under gradient)
        $logoElement = '<image x="' . $padLeft . '" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . $logoDataUri . '" />';
        if (preg_match('/<g mask="url\(#a\)">/i', $svg, $gMatch, PREG_OFFSET_CAPTURE)) {
            $insertPos = $gMatch[0][1] + strlen($gMatch[0][0]);
            $svg = substr($svg, 0, $insertPos) . $logoElement . substr($svg, $insertPos);
        } else {
            $svg = str_replace('</svg>', $logoElement . '</svg>', $svg);
        }

        return $svg;
    }
}
