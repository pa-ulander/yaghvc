<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        Hash::setRounds(4);

        // Set database configuration before any cache operations
        // Allow DB_CONNECTION to be set by environment instead of hardcoding
        if (!env('DB_CONNECTION')) {
            $app['config']->set('database.default', 'sqlite_testing');
        }
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');

        $this->clearCache();

        return $app;
    }

    private function clearCache(): void
    {
        Artisan::call(command: 'cache:clear');
    }
}
