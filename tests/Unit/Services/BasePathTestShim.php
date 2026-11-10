<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

/**
 * Controls the base_path override used by logo processor tests.
 */
class BasePathTestShim
{
    public static bool $throw = false;
}
