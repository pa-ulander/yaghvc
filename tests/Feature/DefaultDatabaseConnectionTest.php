<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DefaultDatabaseConnectionTest extends TestCase
{
    public function testEnvDatabaseConfigurationIsSqliteTesting()
    {
        $this->assertSame('sqlite_testing', getenv('DB_CONNECTION'));
    }

    public function testDefaultDatabaseIsSqliteTesting()
    {
        // dump(database_path('database.sqlite'));
        // dump(database_path('database.sqlite_testing'));
        $this->assertSame('sqlite_testing', config('database.default'));
    }
}