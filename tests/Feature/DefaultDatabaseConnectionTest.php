<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DefaultDatabaseConnectionTest extends TestCase
{
    public function testEnvDatabaseConfigurationIsRespected(): void
    {
        $expectedConnection = env('DB_CONNECTION', 'sqlite_testing');
        $this->assertSame(expected: $expectedConnection, actual: config(key: 'database.default'));
    }

    public function testDefaultDatabaseConnectionIsUsed(): void
    {
        $expectedConnection = env('DB_CONNECTION', 'sqlite_testing');
        $this->assertSame(expected: $expectedConnection, actual: config(key: 'database.default'));
    }
}
