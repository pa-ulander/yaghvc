<?php

declare(strict_types=1);

it(description: 'tests that the ProfileViewsController controller is responding ok')
    ->get(uri: '/?username=testuser')->assertStatus(status: 200);

it(description: 'handles color')
    ->get(uri: '/?username=testuser&color=blue')->assertStatus(status: 200);

it(description: 'handles style')
    ->get(uri: '/?username=testuser&color=blue&style=for-the-badge')->assertStatus(status: 200);

it(description: 'handles base')
    ->get(uri: '/?username=testuser&color=blue&style=for-the-badge&base=123')->assertStatus(status: 200);

it(description: 'handles label')
->get(uri: '/?username=testuser&color=blue&label=hello')->assertStatus(status: 200);

it(description: 'handles abbreviated')
->get(uri: '/?username=testuser&color=blue&label=hello&abbreviated=true')->assertStatus(status: 200);
