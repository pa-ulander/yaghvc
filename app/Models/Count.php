<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webmozart\Assert\Assert;

/**
 * @method static \Database\Factories\CountFactory factory(...$parameters)
 *
 * @package App\Models
 */
class Count extends Model
{
    /** @use HasFactory<\Database\Factories\CountFactory> */
    use HasFactory;

    private int $count;

    public function __construct(int|float $count = 1)
    {
        if (is_float($count)) {
            if (! is_finite($count) || floor($count) !== $count) {
                throw new \InvalidArgumentException(message: 'Number of views must be a whole number');
            }
        }
        if ($count >= PHP_INT_MAX) {
            throw new \InvalidArgumentException(message: 'Max number of views reached');
        }

        if ($count <= 0) {
            throw new \InvalidArgumentException(message: 'Number of views can\'t be negative');
        }

        $this->count = (int) $count;
    }

    public static function ofString(string $value): self
    {
        Assert::digits(
            value: $value,
            message: 'Base count can only be a number',
        );
        $count = (int) $value;

        return new self(count: $count);
    }

    public function toInt(): int
    {
        return $this->count;
    }

    public function plus(self $that): self
    {
        if ($this->count > PHP_INT_MAX - $that->count) {
            throw new \InvalidArgumentException('Max number of views reached');
        }

        $sum = $this->count + $that->count;
        return new self($sum);
    }

    /**
     * @return \Database\Factories\CountFactory
     */
    protected static function newFactory(): \Database\Factories\CountFactory
    {
        return \Database\Factories\CountFactory::new();
    }
}
