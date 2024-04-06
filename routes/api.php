<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CounterController;

Route::get('/', [CounterController::class, 'index']);
