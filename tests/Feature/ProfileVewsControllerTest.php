<?php

declare(strict_types=1);

it(description: 'tests that the ProfileViewsController controller is responding ok')
    ->get(uri: '/?username=testuser')->assertStatus(status: 200);
