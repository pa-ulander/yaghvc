<?php

use App\Http\Controllers\ProfileViewsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProfileViewsController::class, 'index']);
