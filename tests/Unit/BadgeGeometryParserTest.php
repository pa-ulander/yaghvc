<?php

declare(strict_types=1);

use App\Services\BadgeGeometryParser;

it('parses valid poser svg geometry', function () {
    $svg = '<svg width="100" height="20"><rect width="40" height="20" fill="#555"/><rect x="40" width="60" height="20" fill="#97ca00"/><text x="20" y="14">A</text></svg>';
    $parser = new BadgeGeometryParser();
    $result = $parser->parse($svg);
    expect($result->success)->toBeTrue();
    expect($result->totalWidth)->toBe(100.0);
    expect($result->labelWidth)->toBe(40.0);
    expect($result->statusWidth)->toBe(60.0);
});

it('fails when missing total width', function () {
    $svg = '<svg height="20"></svg>';
    $parser = new BadgeGeometryParser();
    $result = $parser->parse($svg);
    expect($result->success)->toBeFalse();
    expect($result->reason)->toBe(BadgeGeometryParser::REASON_NO_TOTAL_WIDTH);
});

it('fails when missing label rect', function () {
    $svg = '<svg width="100" height="20"><rect x="40" width="60" height="20" fill="#97ca00"/></svg>';
    $parser = new BadgeGeometryParser();
    $result = $parser->parse($svg);
    expect($result->success)->toBeFalse();
    expect($result->reason)->toBe(BadgeGeometryParser::REASON_NO_LABEL_RECT);
});

it('fails when missing status rect', function () {
    $svg = '<svg width="100" height="20"><rect width="40" height="20" fill="#555"/></svg>';
    $parser = new BadgeGeometryParser();
    $result = $parser->parse($svg);
    expect($result->success)->toBeFalse();
    expect($result->reason)->toBe(BadgeGeometryParser::REASON_NO_STATUS_RECT);
});

it('fails on width mismatch', function () {
    $svg = '<svg width="120" height="20"><rect width="50" height="20" fill="#555"/><rect x="50" width="60" height="20" fill="#97ca00"/></svg>';
    $parser = new BadgeGeometryParser();
    $result = $parser->parse($svg);
    expect($result->success)->toBeFalse();
    expect($result->reason)->toBe(BadgeGeometryParser::REASON_WIDTH_MISMATCH);
});
