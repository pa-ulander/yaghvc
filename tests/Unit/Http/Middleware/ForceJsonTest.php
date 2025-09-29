<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\ForceJson;
use Illuminate\Http\Request;

it('forces Accept header to application/json', function () {
    $middleware = new ForceJson();
    $request = Request::create('/test', 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html']);

    $nextCalled = false;
    $next = function ($req) use (&$nextCalled) {
        $nextCalled = true;
        return response()->json(['ok' => true]);
    };

    $response = $middleware->handle($request, $next);

    expect($nextCalled)->toBeTrue()
        ->and($request->headers->get('Accept'))->toBe('application/json')
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain('ok');
});
