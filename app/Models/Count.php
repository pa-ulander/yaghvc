<?php

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
            throw new \InvalidArgumentException('The maximum number of views has been reached');
        }
        
        if ($count <= 0) {
            throw new \InvalidArgumentException('Number of views cannot be negative');
        }
    }

    public static function ofString(
        string $value
    ): self {
        Assert::digits(
            $value,
            'The base count must only contain digits',
        );
        $count = intval($value);

        return new self($count);
    }

    public function toInt(): int
    {
        return $this->count;
    }

    public function plus(
        self $that
    ): self {
        $sum = $this->toInt() + $that->toInt();

        if (!is_int($sum)) {
            throw new \InvalidArgumentException(
                'The maximum number of views has been reached',
            );
        }

        return new self($sum);
    }
}
