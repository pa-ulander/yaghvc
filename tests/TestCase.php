<?php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        // $this->seed();
        // $this->artisan('config:cache');
        config(['app.env' => 'testing']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}