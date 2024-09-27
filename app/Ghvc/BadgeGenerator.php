<?php

namespace App\Ghvc;

use App\Models\ProfileViews;

class BadgeGenerator
{
    public function generate(string $username): string
    {
        $count = (new ProfileViews())->count($username);


        // Create a 300x150 image
        $im = imagecreatetruecolor(300, 150);
        $black = imagecolorallocate($im, 0, 0, 0);
        $white = imagecolorallocate($im, 255, 255, 255);

        // Set the background to be white
        imagefilledrectangle($im, 0, 0, 299, 299, $white);

        // Path to our font file
        $font = './arial.ttf';

        // First we create our bounding box for the first text
        $bbox = imagettfbbox(10, 45, $font, 'Powered by PHP ' . phpversion());

        // This is our cordinates for X and Y
        $x = $bbox[0] + imagesx($im) / 2 - $bbox[4] / 2 - 25;
        $y = $bbox[1] + imagesy($im) / 2 - $bbox[5] / 2 - 5;

        // Write it
        imagettftext($im, 10, 45, $x, $y, $black, $font, 'Powered by PHP ' . phpversion());

        // Create the next bounding box for the second text
        $bbox = imagettfbbox(10, 45, $font, 'and Zend Engine ' . zend_version());

        // Set the cordinates so its next to the first text
        $x = $bbox[0] + imagesx($im) / 2 - $bbox[4] / 2 + 10;
        $y = $bbox[1] + imagesy($im) / 2 - $bbox[5] / 2 - 5;

        // Write it
        imagettftext($im, 10, 45, $x, $y, $black, $font, 'and Zend Engine ' . zend_version());

        // Output to browser
        header('Content-Type: image/png');

        imagepng($im);
        imagedestroy($im);


        // Add the proper header
        header('Content-Type: image/svg+xml');

        // Echo the SVG content

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="103.45751953125" height="28" role="img" aria-label="VIEWS: 328">
            <title>VIEWS: 328</title>
            <g shape-rendering="crispEdges">
                <rect width="58.955078125" height="28" fill="#555"/>
                <rect x="58.955078125" width="44.50244140625" height="28" fill="#97ca00"/>
            </g>
            <g 
            fill="#fff" 
            text-anchor="middle" 
            text-rendering="geometricPrecision" 
            font-family="Verdana,Geneva,DejaVu Sans,sans-serif" 
            font-size="10" 
            letter-spacing="1.1">
                <text x="30.5" y="17.5" fill="#fff">VIEWS</text>
                <text x="80.255078125" y="17.5" fill="#fff" font-weight="bold">328</text>
            </g>
        </svg>';


        return response($svg, headers: [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'max-age=30, s-maxage=30, stale-while-revalidate=30',
        ]);
    }


    public function getBadgeBoundaries()
    {
        $labelText = 'Profile Views';
        $fontPath = base_path('resources/fonts/arial/Arial.ttf');

        $labelBoundingBox = imagettfbbox(
            $size = 8,
            $angle = 0,
            $font_filename = $fontPath,
            $text = $labelText,
        );

        $labelWidth = $labelBoundingBox[2];
    }
}
