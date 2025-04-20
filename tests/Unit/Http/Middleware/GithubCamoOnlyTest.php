<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\GithubCamoOnly;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

it('allows github camo client', function () {
    // Arrange
    $middleware = new GithubCamoOnly();
    $request = Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'camo-client');

    $called = false;
    $next = function () use (&$called) {
        $called = true;
        return response('OK', 200);
    };

    // Act
    $response = $middleware->handle($request, $next);

    // Assert
    expect($called)->toBeTrue();
    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('OK');
});

it('allows all user agents in local environment', function () {
    // Arrange
    app()->detectEnvironment(function () {
        return 'local';
    });

    Config::set('auth.github_camo_only', true);

    $middleware = new GithubCamoOnly();
    $request = Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'not-camo-client');

    $called = false;
    $next = function () use (&$called) {
        $called = true;
        return response('OK', 200);
    };

    // Act
    $response = $middleware->handle($request, $next);

    // Assert
    expect($called)->toBeTrue();
    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('OK');
});

it('allows all user agents in testing environment', function () {
    // Arrange
    app()->detectEnvironment(function () {
        return 'testing';
    });

    Config::set('auth.github_camo_only', true);

    $middleware = new GithubCamoOnly();
    $request = Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'not-camo-client');

    $called = false;
    $next = function () use (&$called) {
        $called = true;
        return response('OK', 200);
    };

    // Act
    $response = $middleware->handle($request, $next);

    // Assert
    expect($called)->toBeTrue();
    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('OK');
});

it('blocks non github clients in production', function () {
    // Arrange
    app()->detectEnvironment(function () {
        return 'production';
    });

    Config::set('auth.github_camo_only', true);

    $middleware = new GithubCamoOnly();
    $request = Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'not-camo-client');

    $called = false;
    $next = function () use (&$called) {
        $called = true;
        return response('OK', 200);
    };

    // Act
    $response = $middleware->handle($request, $next);

    // Assert
    expect($called)->toBeFalse('Next middleware should not be called');
    expect($response->getStatusCode())->toBe(403);
    expect($response->getContent())->toBe('Unauthorized');
});
