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
    ): string {
        $message = $this->formatNumber(number: $count, abbreviated: $abbreviated);

        return $this->renderBadge(
            label: $label,
            message: $message,
            messageBackgroundFill: $messageBackgroundFill,
            badgeStyle: $badgeStyle,
            labelColor: $labelColor,
            logo: $logo,
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
    ): string {
        $svg = (string) $this->poser->generate(
            subject: $label,
            status: $message,
            color: $messageBackgroundFill,
            style: $badgeStyle,
            format: Badge::DEFAULT_FORMAT,
        );

        // Apply labelColor if provided
        if ($labelColor) {
            $svg = $this->applyLabelColor($svg, $labelColor);
        }

        // Apply logo if provided
        if ($logo) {
            $svg = $this->applyLogo($svg, $logo);
        }

        return $svg;
    }

    /**
     * This method required because of native `number_format`
     * method has big integer format limitation.
     */
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

    /**
     * Apply custom label color to the SVG badge
     */
    private function applyLabelColor(string $svg, string $labelColor): string
    {
        // Convert named colors to hex if needed
        $hexColor = $this->getHexColor($labelColor);

        // Replace the first rect fill (subject/label color) - more specific pattern
        $pattern = '/(<rect[^>]*)(fill="[^"]*")([^>]*>)/';
        $replacement = '$1fill="#' . $hexColor . '"$3';

        return preg_replace($pattern, $replacement, $svg, 1);
    }

    /**
     * Apply logo to the SVG badge
     */
    private function applyLogo(string $svg, string $logo): string
    {
        try {
            // Extract image data from base64 string
            $imageData = $this->extractImageData($logo);
            if (!$imageData) {
                return $svg;
            }

            // Resize image if needed (keep it small for badges)
            $resizedImage = $this->resizeImageForBadge($imageData['data'], $imageData['mime']);

            // Embed logo in SVG
            return $this->embedLogoInSvg($svg, $resizedImage, $imageData['mime']);
        } catch (\Exception $e) {
            // If logo processing fails, return original SVG
            return $svg;
        }
    }

    /**
     * Convert color name or hex to hex format
     */
    private function getHexColor(string $color): string
    {
        // Remove # if present
        $color = ltrim($color, '#');

        // If it's already a hex color, return it
        if (preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return $color;
        }

        // Convert named colors to hex
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

    /**
     * Extract image data from base64 string
     */
    private function extractImageData(string $logo): ?array
    {
        $pattern = '/^data:image\/(png|jpeg|gif|svg\+xml);base64,([A-Za-z0-9+\/]+={0,2})$/';
        if (!preg_match($pattern, $logo, $matches)) {
            return null;
        }

        return [
            'mime' => $matches[1],
            'data' => base64_decode($matches[2])
        ];
    }

    /**
     * Resize image for badge (keep it small)
     */
    private function resizeImageForBadge(string $imageData, string $mime): string
    {
        // For now, return the original image data without resizing
        // TODO: Implement proper image resizing with Intervention\Image
        return 'data:image/' . $mime . ';base64,' . base64_encode($imageData);
    }

    /**
     * Embed logo in SVG
     */
    private function embedLogoInSvg(string $svg, string $logoDataUri, string $mime): string
    {
        // Add logo as image element positioned in the subject area (left side)
        // Position it at x=5, y=3 with 14x14 size to fit nicely in the badge
        $logoElement = '<image x="5" y="3" width="14" height="14" href="' . $logoDataUri . '" />';

        // Insert logo before the closing </svg> tag
        return str_replace('</svg>', $logoElement . '</svg>', $svg);
    }
}
