<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    // use DatabaseTransactions;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // explicitly set the database connection
        config(['database.default' => 'sqlite_testing']);
        config(['cache.default' => 'array']);
        config(['session.driver' => 'array']);
        config(['queue.default' => 'sync']);

        // now it's safe to clear cache
        Artisan::call('cache:clear');

        // other configurations
        config(['app.env' => 'testing']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
