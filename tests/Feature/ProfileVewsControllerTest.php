<?php

use Illuminate\Http\Request;
use App\Http\Controllers\ProfileViewsController;

it('tests that the ProfileViewsController controller is responding ok')
    ->get('/?username=testuser')->assertStatus(200);
