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
        // $this->seed();
        // $this->artisan('config:cache');
        config(['app.env' => 'testing']);
        Artisan::call('cache:clear');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
