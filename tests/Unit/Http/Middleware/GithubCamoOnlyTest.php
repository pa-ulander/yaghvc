<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\GithubCamoOnly;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

it('allows all requests when github_camo_only is disabled', function () {
    // Arrange
    Config::set('auth.github_camo_only', false);

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

it('allows github camo client', function () {
    // Arrange
    Config::set('auth.github_camo_only', true);

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

it('allows all user agents when allow_all_user_agents is enabled', function () {
    // Arrange
    Config::set('auth.github_camo_only', true);
    Config::set('auth.allow_all_user_agents', true);

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

it('allows all user agents in environments listed in environment_exceptions', function () {
    // Arrange
    Config::set('auth.github_camo_only', true);
    Config::set('auth.allow_all_user_agents', false);
    Config::set('auth.environment_exceptions', ['local', 'testing']);

    app()->detectEnvironment(function () {
        return 'local';
    });

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

it('blocks non github clients in production when restrictions are enabled', function () {
    // Arrange
    Config::set('auth.github_camo_only', true);
    Config::set('auth.allow_all_user_agents', false);
    Config::set('auth.environment_exceptions', ['local', 'testing']);

    app()->detectEnvironment(function () {
        return 'production';
    });

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

it('normalises boolean configuration values', function () {
    $middleware = new GithubCamoOnly();
    $method = new \ReflectionMethod(GithubCamoOnly::class, 'boolConfig');
    $method->setAccessible(true);

    Config::set('auth.test.bool_true', 'YES');
    Config::set('auth.test.bool_false', 'no');
    Config::set('auth.test.bool_numeric', 1);
    Config::set('auth.test.bool_invalid', ['nope']);

    expect($method->invoke($middleware, 'auth.test.bool_true', false))->toBeTrue()
        ->and($method->invoke($middleware, 'auth.test.bool_false', true))->toBeFalse()
        ->and($method->invoke($middleware, 'auth.test.bool_numeric', false))->toBeTrue()
        ->and($method->invoke($middleware, 'auth.test.bool_invalid', true))->toBeTrue();
});

it('filters string list configuration values', function () {
    $middleware = new GithubCamoOnly();
    $method = new \ReflectionMethod(GithubCamoOnly::class, 'stringListConfig');
    $method->setAccessible(true);

    Config::set('auth.test.list_valid', ['prod', 'staging', '']);
    Config::set('auth.test.list_invalid', 'prod');
    Config::set('auth.test.list_empty', [1, null]);

    expect($method->invoke($middleware, 'auth.test.list_valid', ['fallback']))->toBe(['prod', 'staging'])
        ->and($method->invoke($middleware, 'auth.test.list_invalid', ['fallback']))->toBe(['fallback'])
        ->and($method->invoke($middleware, 'auth.test.list_empty', ['fallback']))->toBe(['fallback']);
});
