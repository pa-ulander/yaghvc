<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable value object encapsulating badge rendering configuration.
 *
 * This object holds all parameters needed to render a badge, ensuring
 * type safety and validation at the boundary between request data and
 * rendering logic.
 */
readonly class BadgeConfiguration
{
    /**
     * @param string $label The text to display in the badge label section
     * @param string $color The background color for the message section (hex or named color)
     * @param string $style The badge rendering style (flat, flat-square, for-the-badge, plastic)
     * @param bool $abbreviated Whether to abbreviate large numbers (e.g., 1.2k instead of 1234)
     * @param string|null $labelColor Optional background color for the label section
     * @param string|null $logoColor Optional color for SVG logos (hex, named, or 'auto' for contrast)
     * @param string|null $logo Optional logo (simple-icons slug, data URI, or raw base64)
     * @param string|null $logoSize Optional logo size ('auto' or numeric 8-64)
     */
    public function __construct(
        public string $label,
        public string $color,
        public string $style,
        public bool $abbreviated,
        public ?string $labelColor = null,
        public ?string $logoColor = null,
        public ?string $logo = null,
        public ?string $logoSize = null,
    ) {
        $this->validateStyle();
    }

    /**
     * Create instance from validated request data.
     *
     * @param array<string, mixed> $data Validated request data
     * @return self
     */
    public static function fromValidatedRequest(array $data): self
    {
        return new self(
            label: self::stringFromArray($data, 'label', 'Visits'),
            color: self::stringFromArray($data, 'color', 'blue'),
            style: self::stringFromArray($data, 'style', 'for-the-badge'),
            abbreviated: self::boolFromArray($data, 'abbreviated', false),
            labelColor: self::nullableStringFromArray($data, 'labelColor'),
            logoColor: self::nullableStringFromArray($data, 'logoColor'),
            logo: self::nullableStringFromArray($data, 'logo'),
            logoSize: self::nullableStringFromArray($data, 'logoSize'),
        );
    }

    /**
     * Validate that the style is one of the allowed values.
     *
     * @throws InvalidArgumentException If style is invalid
     */
    private function validateStyle(): void
    {
        $allowedStyles = ['flat', 'flat-square', 'for-the-badge', 'plastic'];

        if (! in_array($this->style, $allowedStyles, true)) {
            throw new InvalidArgumentException(
                "Invalid badge style '{$this->style}'. Allowed: " . implode(', ', $allowedStyles)
            );
        }
    }

    /**
     * Extract string value from array with default.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @param string $default
     * @return string
     */
    private static function stringFromArray(array $data, string $key, string $default): string
    {
        if (! array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * Extract nullable string value from array.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @return string|null
     */
    private static function nullableStringFromArray(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * Extract boolean value from array with default.
     *
     * @param array<string, mixed> $data
     * @param string $key
     * @param bool $default
     * @return bool
     */
    private static function boolFromArray(array $data, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = strtolower($value);
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return $default;
    }
}
