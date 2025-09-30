<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Immutable value object representing a processed logo result.
 *
 * Standardizes the output from logo handlers in the Chain of Responsibility.
 */
final readonly class LogoResult
{
    /**
     * @param string $dataUri The data URI for the logo
     * @param int $width The width in pixels
     * @param int $height The height in pixels
     * @param string $mime The MIME type (e.g., 'png', 'svg+xml')
     * @param string|null $binary Optional binary data for the logo
     */
    public function __construct(
        public string $dataUri,
        public int $width,
        public int $height,
        public string $mime,
        public ?string $binary = null,
    ) {}

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array{dataUri:string,width:int,height:int,mime:string,binary?:string}
     */
    public function toArray(): array
    {
        $result = [
            'dataUri' => $this->dataUri,
            'width' => $this->width,
            'height' => $this->height,
            'mime' => $this->mime,
        ];

        if ($this->binary !== null) {
            $result['binary'] = $this->binary;
        }

        return $result;
    }

    /**
     * Create from array (for cache deserialization).
     *
     * @param array{dataUri:string,width:int,height:int,mime:string,binary?:string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            dataUri: $data['dataUri'],
            width: $data['width'],
            height: $data['height'],
            mime: $data['mime'],
            binary: $data['binary'] ?? null,
        );
    }
}
