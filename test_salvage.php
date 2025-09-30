<?php

declare(strict_types=1);

$svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>';
$b64 = base64_encode($svg);
$original = 'data:image/svg+xml;base64, ' . $b64 . ' ';
echo "Original: $original\n";

// Simulate decodeDataUrlFromQueryParam
$candidate = $original;
$candidate = str_replace(' ', '+', $candidate);
echo "After space->+: $candidate\n";
$candidate = preg_replace('/\s+/', '', $candidate) ?? '';
echo "After removing whitespace: $candidate\n";

// Test primary regex
if (preg_match('#^data:image/(png|jpeg|jpg|gif|svg\+xml);base64,([A-Za-z0-9+/=]+)$#', $candidate, $m)) {
    echo "PRIMARY REGEX MATCHES!\n";
    echo "MIME: {$m[1]}\n";
    echo "Base64 (first 30): " . substr($m[2], 0, 30) . "\n";
} else {
    echo "Primary regex does not match\n";
}
