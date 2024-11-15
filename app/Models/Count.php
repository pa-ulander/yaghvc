<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webmozart\Assert\Assert;

/**
 * @method static \Database\Factories\CountFactory factory(...$parameters)
 */
class Count extends Model
{
    use HasFactory;

    private int|float $count;

    public function __construct(int|float $count)
    {
        if ($count >= PHP_INT_MAX) {
            throw new \InvalidArgumentException(message: 'Max number of views reached');
        }

        if ($count <= 0) {
            throw new \InvalidArgumentException(message: 'Number of views can\'t be negative');
        }

        $this->count = $count;
    }

    public static function ofString(
        string $value
    ): self {
        Assert::digits(
            value: $value,
            message: 'Base count can only be a number',
        );
        $count = intval(value: $value);

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
}
