<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CountController extends Controller
{

    public function index(Request $request)
    {
        return [
            'message' => 'Hello, world!',
            'username' => $request->username,
            'method' => $request->method(),
        ];
    }
}
