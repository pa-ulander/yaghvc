<?php

declare(strict_types=1);

use App\Models\ProfileViews;

it(description: 'creates a new profile view', closure: function (): void {
    $profileView = ProfileViews::create(attributes: ['username' => 'testuser']);

    expect(value: $profileView->username)->toBe(expected: 'testuser');
});

it(description: 'checks if a username has been added to the database', closure: function (): void {
    ProfileViews::create(attributes: ['username' => 'testuser']);

    $profileView = ProfileViews::where(column: 'username', operator: 'testuser')->first();

    expect(value: $profileView)->not()->toBeNull();
    expect(value: $profileView->username)->toBe(expected: 'testuser');
});
