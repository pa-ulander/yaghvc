<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DefaultDatabaseConnectionTest extends TestCase
{
    public function testEnvDatabaseConfigurationIsSqliteTesting(): void
    {
        $this->assertSame(expected: 'sqlite_testing', actual: getenv('DB_CONNECTION'));
    }

    public function testDefaultDatabaseIsSqliteTesting(): void
    {
        // dump(database_path('database.sqlite'));
        // dump(database_path('database.sqlite_testing'));
        $this->assertSame(expected: 'sqlite_testing', actual: config(key: 'database.default'));
    }
}