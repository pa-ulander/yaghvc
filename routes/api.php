<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CountController;

Route::get('/', [CountController::class, 'index']);
