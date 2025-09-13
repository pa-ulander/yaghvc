#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Helper script: Generate a percent-encoded data URI for an image (png|jpg|jpeg|gif|svg).
 * Usage:
 *   php scripts/encode-logo.php path/to/logo.png [--mime=image/png] [--inline]
 * If --mime not provided it will be inferred from extension.
 * Default output: single line (no newline) suitable for appending as &logo=<output>
 * With --inline: emits a full Markdown snippet including the encoded logo query parameter.
 */

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/encode-logo.php <file> [--mime=<mime>] [--inline]\n");
    exit(1);
}
$file = $argv[1];
if (!is_readable($file)) {
    fwrite(STDERR, "File not readable: $file\n");
    exit(1);
}
$mime = null;
$inline = false;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--mime=')) {
        $mime = substr($arg, 7);
    } elseif ($arg === '--inline') {
        $inline = true;
    }
}
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$defaultMap = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
];
if ($mime === null) {
    $mime = $defaultMap[$ext] ?? null;
}
if ($mime === null) {
    fwrite(STDERR, "Cannot infer MIME type. Provide --mime=...\n");
    exit(1);
}
$raw = file_get_contents($file);
if ($raw === false) {
    fwrite(STDERR, "Failed reading file\n");
    exit(1);
}
// For SVG, strip newlines and tabs to reduce size before encoding.
if ($ext === 'svg') {
    $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;
}
$base64 = base64_encode($raw);
$dataUri = 'data:' . $mime . ';base64,' . $base64;
$encoded = rawurlencode($dataUri);

if ($inline === true) {
    // Provide a ready-to-paste Markdown badge snippet. Username placeholder helps quick usage.
    // User can replace <your-username> and optionally add other params.
    $snippet = '![](https://ghvc.kabelkultur.se?username=<your-username>&logo=' . $encoded . ')';
    echo $snippet; // no newline (consistent with original behavior)
    exit(0);
}

// Default: output only the encoded string (no trailing newline to ease shell use)
echo $encoded;
