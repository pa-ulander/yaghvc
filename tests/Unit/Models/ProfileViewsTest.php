<?php

declare(strict_types=1);

use App\Models\ProfileViews;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->profileViews = new ProfileViews();
});

test(description: 'create a new profile view', closure: function (): void {
    $profileView = ProfileViews::create(attributes: ['username' => 'testuser']);

    expect(value: $profileView->username)->toBe(expected: 'testuser');
});

test(description: 'username should be added to the database', closure: function (): void {
    ProfileViews::create(attributes: ['username' => 'testuser']);

    $profileView = ProfileViews::where(column: 'username', operator: 'testuser')->first();

    expect(value: $profileView)->not()->toBeNull();
    expect(value: $profileView->username)->toBe(expected: 'testuser');
});


test('fillable attributes are set correctly', function () {
    $fillable = ['username', 'repository', 'visit_count', 'last_visit'];
    expect($this->profileViews->getFillable())->toBe($fillable);
});

test('last_visit is cast to datetime', function () {
    $casts = $this->profileViews->getCasts();
    expect($casts['last_visit'])->toBe('datetime');
});

test('incrementCount increases visit_count and updates last_visit', function () {
    $profileView = ProfileViews::factory()->create(['username' => 'testuser', 'visit_count' => 5]);

    $profileView->incrementCount();

    $updatedProfileView = ProfileViews::find($profileView->id);
    expect($updatedProfileView->visit_count)->toBe(6);
    expect($updatedProfileView->last_visit->timestamp)->toBeGreaterThan(time() - 10);
});


test('incrementCount throws exception when username is missing', function () {
    $profileView = new ProfileViews();

    Log::shouldReceive('error')->once()->with(
        'Attempt to increment count for ProfileView without username',
        ['id' => null]
    );

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Username is missing for this instance.');

    $profileView->incrementCount();
});

test('timestamps are enabled', function () {
    expect($this->profileViews->timestamps)->toBeTrue();
});

test('UPDATED_AT constant is null', function () {
    expect(ProfileViews::UPDATED_AT)->toBeNull();
});
