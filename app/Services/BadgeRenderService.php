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
            if (!$prepared) {
                return $svg;
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


    private function embedLogoInSvg(string $svg, string $logoDataUri, string $mime, int $width = 14, int $height = 14): string
    {
        // Adjust y so smaller heights remain vertically centered within typical 18px badge height
        $badgeHeight = 18;
        $y = (int) max(0, floor(($badgeHeight - $height) / 2));
        $logoElement = '<image x="5" y="' . $y . '" width="' . $width . '" height="' . $height . '" href="' . $logoDataUri . '" />';
        return str_replace('</svg>', $logoElement . '</svg>', $svg);
    }
}
