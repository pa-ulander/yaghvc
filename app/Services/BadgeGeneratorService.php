<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProfileViews;

class BadgeGeneratorService
{
    public function generate(string $username): string
    {
        $count = (new ProfileViews())->getCount($username);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="103.45751953125" height="28" role="img" aria-label="VIEWS: ' . $count . '">
            <title>VIEWS: ' . $count . '</title>
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
                <text x="80.255078125" y="17.5" fill="#fff" font-weight="bold">' . $count . '</text>
            </g>
        </svg>';


        return $svg;
    }
}
