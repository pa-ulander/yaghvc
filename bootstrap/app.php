<?php

use App\Http\Middleware\ForceJson;
use App\Http\Middleware\GithubCamoOnly;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(path: __DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(callback: function (Middleware $middleware): void {
        $middleware->web(append: [
            ForceJson::class,
            GithubCamoOnly::class,
        ]);

        $middleware->api(prepend: [
            ForceJson::class,
            GithubCamoOnly::class,
        ]);
    })
    ->withExceptions(using: function (Exceptions $exceptions): void {
        //
    })->create();
