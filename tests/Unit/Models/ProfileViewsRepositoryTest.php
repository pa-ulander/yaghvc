<?php

declare(strict_types=1);

use App\Models\ProfileViews;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->profileViews = new ProfileViews();
});

describe('Repository Support', function () {
    test('creates profile view with repository', function () {
        $profileView = ProfileViews::create([
            'username' => 'testuser',
            'repository' => 'test-repo',
            'visit_count' => 1,
        ]);

        expect($profileView->username)->toBe('testuser');
        expect($profileView->repository)->toBe('test-repo');
        expect($profileView->visit_count)->toBe(1);
    });

    test('creates profile view without repository (profile view)', function () {
        $profileView = ProfileViews::create([
            'username' => 'testuser',
            'repository' => null,
            'visit_count' => 1,
        ]);

        expect($profileView->username)->toBe('testuser');
        expect($profileView->repository)->toBeNull();
        expect($profileView->visit_count)->toBe(1);
    });

    test('can have same username with different repositories', function () {
        $profileView1 = ProfileViews::create([
            'username' => 'testuser',
            'repository' => null,
            'visit_count' => 5,
        ]);

        $profileView2 = ProfileViews::create([
            'username' => 'testuser',
            'repository' => 'repo1',
            'visit_count' => 3,
        ]);

        $profileView3 = ProfileViews::create([
            'username' => 'testuser',
            'repository' => 'repo2',
            'visit_count' => 7,
        ]);

        expect($profileView1->visit_count)->toBe(5);
        expect($profileView2->visit_count)->toBe(3);
        expect($profileView3->visit_count)->toBe(7);
    });

    test('getCount works for profile views (no repository)', function () {
        ProfileViews::create([
            'username' => 'testuser',
            'repository' => null,
            'visit_count' => 10,
        ]);

        $count = (new ProfileViews())->getCount('testuser');
        expect($count)->toBe(10);
    });

    test('getCount works for repository-specific views', function () {
        ProfileViews::create([
            'username' => 'testuser',
            'repository' => 'my-repo',
            'visit_count' => 15,
        ]);

        $count = (new ProfileViews())->getCount('testuser', 'my-repo');
        expect($count)->toBe(15);
    });

    test('getCount returns 0 for non-existent repository', function () {
        $count = (new ProfileViews())->getCount('testuser', 'non-existent-repo');
        expect($count)->toBe(0);
    });

    test('getCount distinguishes between profile and repository views', function () {
        // Create profile view
        ProfileViews::create([
            'username' => 'testuser',
            'repository' => null,
            'visit_count' => 10,
        ]);

        // Create repository view
        ProfileViews::create([
            'username' => 'testuser',
            'repository' => 'my-repo',
            'visit_count' => 5,
        ]);

        $profileCount = (new ProfileViews())->getCount('testuser');
        $repoCount = (new ProfileViews())->getCount('testuser', 'my-repo');

        expect($profileCount)->toBe(10);
        expect($repoCount)->toBe(5);
    });

    test('incrementCount works with repository', function () {
        $profileView = ProfileViews::create([
            'username' => 'testuser',
            'repository' => 'my-repo',
            'visit_count' => 5,
        ]);

        $profileView->incrementCount();

        $updatedProfileView = ProfileViews::find($profileView->id);
        expect($updatedProfileView->visit_count)->toBe(6);
        expect($updatedProfileView->last_visit->timestamp)->toBeGreaterThan(time() - 10);
    });

    test('incrementCount clears correct cache for repository views', function () {
        $profileView = ProfileViews::factory()->create([
            'username' => 'testuser',
            'repository' => 'my-repo',
            'visit_count' => 5,
        ]);

        // Pre-populate cache
        Cache::put('count-testuser-my-repo', 999, 60);
        expect(Cache::get('count-testuser-my-repo'))->toBe(999);

        $profileView->incrementCount();

        // Cache should be cleared
        expect(Cache::get('count-testuser-my-repo'))->toBeNull();
    });

    test('incrementCount clears correct cache for profile views', function () {
        $profileView = ProfileViews::factory()->create([
            'username' => 'testuser',
            'repository' => null,
            'visit_count' => 5,
        ]);

        // Pre-populate cache
        Cache::put('count-testuser', 999, 60);
        expect(Cache::get('count-testuser'))->toBe(999);

        $profileView->incrementCount();

        // Cache should be cleared
        expect(Cache::get('count-testuser'))->toBeNull();
    });
});

describe('Factory Support', function () {
    test('factory creates profile view by default', function () {
        $profileView = ProfileViews::factory()->create();

        expect($profileView->username)->toBeString();
        expect($profileView->repository)->toBeNull();
    });

    test('factory can create repository-specific view', function () {
        $profileView = ProfileViews::factory()->forRepository('test-repo')->create();

        expect($profileView->username)->toBeString();
        expect($profileView->repository)->toBe('test-repo');
    });
});

// Keep existing tests
test('create a new profile view', function () {
    $profileView = ProfileViews::create(['username' => 'testuser']);

    expect($profileView->username)->toBe('testuser');
});

test('username should be added to the database', function () {
    ProfileViews::create(['username' => 'testuser']);

    $profileView = ProfileViews::where('username', 'testuser')->first();

    expect($profileView)->not()->toBeNull();
    expect($profileView->username)->toBe('testuser');
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
