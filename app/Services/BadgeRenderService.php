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

        if ($labelColor) {
            $svg = $this->applyLabelColor($svg, $labelColor);
        }

        if ($logo) {
            $svg = $this->applyLogo($svg, $logo);
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

    private function applyLogo(string $svg, string $logo): string
    {
        try {
            $imageData = $this->extractImageData($logo);
            if (!$imageData) {
                return $svg;
            }

            $resizedImage = $this->resizeImageForBadge($imageData['data'], $imageData['mime']);

            return $this->embedLogoInSvg($svg, $resizedImage, $imageData['mime']);
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

    private function extractImageData(string $logo): ?array
    {
        $decodedLogo = urldecode($logo);
        $commaPos = strpos($decodedLogo, ',');
        if ($commaPos === false) {
            return null;
        }
        $prefix = substr($decodedLogo, 0, $commaPos);
        $dataPart = substr($decodedLogo, $commaPos + 1);

        if (!preg_match('/^data:image\/(png|jpeg|jpg|gif|svg\+xml);base64$/', $prefix)) {
            return null;
        }

        $dataPart = str_replace(' ', '+', $dataPart);
        $dataPart = preg_replace('/[\r\n\t]+/', '', $dataPart);
        if ($dataPart === null) {
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $dataPart)) {
            return null;
        }

        $decoded = base64_decode($dataPart, true);
        if ($decoded === false || $decoded === '') {
            return null;
        }

        $mime = substr($prefix, 11, strpos($prefix, ';') - 11);

        return [
            'mime' => $mime,
            'data' => $decoded,
        ];
    }

    private function resizeImageForBadge(string $imageData, string $mime): string
    {
        // TODO: Implement proper image resizing with Intervention\Image
        return 'data:image/' . $mime . ';base64,' . base64_encode($imageData);
    }

    private function embedLogoInSvg(string $svg, string $logoDataUri, string $mime): string
    {
        $logoElement = '<image x="2" y="6" width="16" height="16" href="' . $logoDataUri . '" />';
        return str_replace('</svg>', $logoElement . '</svg>', $svg);
    }
}
