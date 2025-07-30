<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\ProfileViews;
use App\Repositories\ProfileViewsRepository;

beforeEach(function () {
    $this->repository = new ProfileViewsRepository();
});

describe('Repository Support', function () {
    test('findOrCreate works without repository (profile views)', function () {
        $profileView = $this->repository->findOrCreate('testuser');

        expect($profileView)->toBeInstanceOf(ProfileViews::class);
        expect($profileView->username)->toBe('testuser');
        expect($profileView->repository)->toBeNull();
        expect($profileView->visit_count)->toBe(1); // Should be incremented
    });

    test('findOrCreate works with repository', function () {
        $profileView = $this->repository->findOrCreate('testuser', 'my-repo');

        expect($profileView)->toBeInstanceOf(ProfileViews::class);
        expect($profileView->username)->toBe('testuser');
        expect($profileView->repository)->toBe('my-repo');
        expect($profileView->visit_count)->toBe(1); // Should be incremented
    });

    test('findOrCreate increments existing profile view', function () {
        // Create initial record
        ProfileViews::create([
            'username' => 'testuser',
            'repository' => null,
            'visit_count' => 5,
        ]);

        $profileView = $this->repository->findOrCreate('testuser');

        expect($profileView->visit_count)->toBe(6); // Should be incremented
    });

    test('findOrCreate increments existing repository view', function () {
        // Create initial record
        ProfileViews::create([
            'username' => 'testuser',
            'repository' => 'my-repo',
            'visit_count' => 3,
        ]);

        $profileView = $this->repository->findOrCreate('testuser', 'my-repo');

        expect($profileView->visit_count)->toBe(4); // Should be incremented
    });

    test('findOrCreate handles profile and repository views separately', function () {
        // Create profile view
        $profileView1 = $this->repository->findOrCreate('testuser');
        expect($profileView1->repository)->toBeNull();
        expect($profileView1->visit_count)->toBe(1);

        // Create repository view for same user
        $profileView2 = $this->repository->findOrCreate('testuser', 'my-repo');
        expect($profileView2->repository)->toBe('my-repo');
        expect($profileView2->visit_count)->toBe(1);

        // Increment profile view again
        $profileView3 = $this->repository->findOrCreate('testuser');
        expect($profileView3->repository)->toBeNull();
        expect($profileView3->visit_count)->toBe(2);

        // Increment repository view again
        $profileView4 = $this->repository->findOrCreate('testuser', 'my-repo');
        expect($profileView4->repository)->toBe('my-repo');
        expect($profileView4->visit_count)->toBe(2);
    });

    test('findOrCreate handles multiple repositories for same user', function () {
        $repo1View = $this->repository->findOrCreate('testuser', 'repo1');
        $repo2View = $this->repository->findOrCreate('testuser', 'repo2');

        expect($repo1View->repository)->toBe('repo1');
        expect($repo2View->repository)->toBe('repo2');
        expect($repo1View->visit_count)->toBe(1);
        expect($repo2View->visit_count)->toBe(1);

        // Increment first repo again
        $repo1ViewAgain = $this->repository->findOrCreate('testuser', 'repo1');
        expect($repo1ViewAgain->visit_count)->toBe(2);

        // Second repo should still be at 1
        $repo2ViewCheck = ProfileViews::where('username', 'testuser')
            ->where('repository', 'repo2')
            ->first();
        expect($repo2ViewCheck->visit_count)->toBe(1);
    });
});
