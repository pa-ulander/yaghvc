<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CounterController extends Controller
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [

    ];

    public function index(Request $request)
    {
        return [
            'message' => 'Hello, world!',
            'username' => $request->username,
            'method' => $request->method(),
        ];
    }
}
