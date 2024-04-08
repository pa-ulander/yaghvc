<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Webmozart\Assert\Assert;

class ProfileViewsController extends Controller
{

    public function index(Request $request)
    {
        Assert::string($request->username);

        return [
            'message' => 'Hello, world!',
            'username' => $request->username,
            'method' => $request->method(),
        ];
    }
}
