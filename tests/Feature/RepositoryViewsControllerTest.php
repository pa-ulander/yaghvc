<?php

declare(strict_types=1);

it('handles repository parameter in feature test')
    ->get(uri: '/?username=testuser&repository=test-repo')
    ->assertStatus(status: 200);

it('handles repository with other parameters')
    ->get(uri: '/?username=testuser&repository=my-project&color=green&style=flat&label=Repository%20Views')
    ->assertStatus(status: 200);

it('maintains backward compatibility without repository parameter')
    ->get(uri: '/?username=testuser')
    ->assertStatus(status: 200);

it('handles repository with base count')
    ->get(uri: '/?username=testuser&repository=my-repo&base=100')
    ->assertStatus(status: 200);

it('handles repository with abbreviation')
    ->get(uri: '/?username=testuser&repository=my-repo&abbreviated=true')
    ->assertStatus(status: 200);
