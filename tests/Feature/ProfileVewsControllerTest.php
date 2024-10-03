<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use App\Http\Controllers\ProfileViewsController;


it(description: 'tests that the ProfileViewsController controller is responding ok')
    ->get(uri: '/?username=testuser')->assertStatus(status: 200);
