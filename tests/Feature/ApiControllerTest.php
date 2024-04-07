<?php

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

it('tests the index method of ApiController', function () {
    $request = Request::create('/api', 'GET', [
        'username' => 'testuser',
    ]);

    $controller = new ApiController();
    $response = $controller->index($request);

    expect($response['message'])->toBe('Hello, world!');
    expect($response['username'])->toBe('testuser');
    expect($response['method'])->toBe('GET');
});