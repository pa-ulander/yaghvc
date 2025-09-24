<?php

use App\Http\Controllers\ProfileViewsController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:badge')->get('/', [ProfileViewsController::class, 'index']);
