<?php

use App\Models\ProfileViews;

it('creates a new profile view', function () {
    $profileView = ProfileViews::create(['username' => 'testuser']);

    expect($profileView->username)->toBe('testuser');
});

it('checks if a username has been added to the database', function () {
    ProfileViews::create(['username' => 'testuser']);

    $profileView = ProfileViews::where('username', 'testuser')->first();

    expect($profileView)->not()->toBeNull();
    expect($profileView->username)->toBe('testuser');
});