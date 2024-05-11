<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileViewsController;

Route::get('/', [ProfileViewsController::class, 'index']);
