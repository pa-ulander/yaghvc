<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webmozart\Assert\Assert;

class Count extends Model
{
    use HasFactory;

    private int $count;

    public function __construct(int $count)
    {
        $this->count = $count;

        if ($count > PHP_INT_MAX) {
            throw new \InvalidArgumentException(message: 'Max number of views reached');
        }

        if ($count <= 0) {
            throw new \InvalidArgumentException(message: 'Number of views can\'t be negative');
        }
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

    public function plus(
        self $that
    ): self {
        $sum = $this->toInt() + $that->toInt();

        if (! is_int(value: $sum)) {
            throw new \InvalidArgumentException(
                message: 'Max number of views reached',
            );
        }

        return new self(count: $sum);
    }
}
