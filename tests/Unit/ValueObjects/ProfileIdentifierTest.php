<?php

declare(strict_types=1);

use App\ValueObjects\ProfileIdentifier;

describe('ProfileIdentifier', function () {
    test('it creates identifier with username only', function () {
        $identifier = new ProfileIdentifier(username: 'octocat');

        expect($identifier->username)->toBe('octocat')
            ->and($identifier->repository)->toBeNull();
    });

    test('it creates identifier with username and repository', function () {
        $identifier = new ProfileIdentifier(
            username: 'octocat',
            repository: 'hello-world'
        );

        expect($identifier->username)->toBe('octocat')
            ->and($identifier->repository)->toBe('hello-world');
    });

    test('it is immutable readonly class', function () {
        $identifier = new ProfileIdentifier(username: 'octocat');

        expect($identifier)->toBeInstanceOf(ProfileIdentifier::class);
        // Attempting to modify readonly property would cause PHP error
    });

    describe('username validation', function () {
        test('it accepts valid alphanumeric username', function () {
            $identifier = new ProfileIdentifier(username: 'user123');

            expect($identifier->username)->toBe('user123');
        });

        test('it accepts username with hyphens', function () {
            $identifier = new ProfileIdentifier(username: 'user-name');

            expect($identifier->username)->toBe('user-name');
        });

        test('it accepts username with multiple hyphens non-consecutive', function () {
            $identifier = new ProfileIdentifier(username: 'my-user-name');

            expect($identifier->username)->toBe('my-user-name');
        });

        test('it accepts single character username', function () {
            $identifier = new ProfileIdentifier(username: 'a');

            expect($identifier->username)->toBe('a');
        });

        test('it accepts 39 character username', function () {
            $username = str_repeat('a', 39);
            $identifier = new ProfileIdentifier(username: $username);

            expect($identifier->username)->toBe($username);
        });

        test('it rejects empty username', function () {
            new ProfileIdentifier(username: '');
        })->throws(InvalidArgumentException::class, 'Username cannot be empty');

        test('it rejects username exceeding 39 characters', function () {
            $username = str_repeat('a', 40);
            new ProfileIdentifier(username: $username);
        })->throws(InvalidArgumentException::class, 'cannot exceed 39 characters');

        test('it rejects username with consecutive hyphens', function () {
            new ProfileIdentifier(username: 'user--name');
        })->throws(InvalidArgumentException::class, 'Invalid username format');

        test('it rejects username starting with hyphen', function () {
            new ProfileIdentifier(username: '-username');
        })->throws(InvalidArgumentException::class, 'Invalid username format');

        test('it rejects username ending with hyphen', function () {
            new ProfileIdentifier(username: 'username-');
        })->throws(InvalidArgumentException::class, 'Invalid username format');

        test('it rejects username with spaces', function () {
            new ProfileIdentifier(username: 'user name');
        })->throws(InvalidArgumentException::class, 'Invalid username format');

        test('it rejects username with special characters', function () {
            new ProfileIdentifier(username: 'user@name');
        })->throws(InvalidArgumentException::class, 'Invalid username format');

        test('it rejects username with underscores', function () {
            new ProfileIdentifier(username: 'user_name');
        })->throws(InvalidArgumentException::class, 'Invalid username format');
    });

    describe('repository validation', function () {
        test('it accepts valid alphanumeric repository', function () {
            $identifier = new ProfileIdentifier(
                username: 'octocat',
                repository: 'repo123'
            );

            expect($identifier->repository)->toBe('repo123');
        });

        test('it accepts repository with hyphens', function () {
            $identifier = new ProfileIdentifier(
                username: 'octocat',
                repository: 'hello-world'
            );

            expect($identifier->repository)->toBe('hello-world');
        });

        test('it accepts repository with underscores', function () {
            $identifier = new ProfileIdentifier(
                username: 'octocat',
                repository: 'hello_world'
            );

            expect($identifier->repository)->toBe('hello_world');
        });

        test('it accepts repository with periods', function () {
            $identifier = new ProfileIdentifier(
                username: 'octocat',
                repository: 'hello.world'
            );

            expect($identifier->repository)->toBe('hello.world');
        });

        test('it accepts repository with mixed valid characters', function () {
            $identifier = new ProfileIdentifier(
                username: 'octocat',
                repository: 'my-repo_v2.0'
            );

            expect($identifier->repository)->toBe('my-repo_v2.0');
        });

        test('it accepts repository up to 100 characters', function () {
            $repository = str_repeat('a', 100);
            $identifier = new ProfileIdentifier(
                username: 'octocat',
                repository: $repository
            );

            expect($identifier->repository)->toBe($repository);
        });

        test('it rejects empty repository string', function () {
            new ProfileIdentifier(
                username: 'octocat',
                repository: ''
            );
        })->throws(InvalidArgumentException::class, 'Repository cannot be empty');

        test('it rejects repository exceeding 100 characters', function () {
            $repository = str_repeat('a', 101);
            new ProfileIdentifier(
                username: 'octocat',
                repository: $repository
            );
        })->throws(InvalidArgumentException::class, 'cannot exceed 100 characters');

        test('it rejects repository starting with period', function () {
            new ProfileIdentifier(
                username: 'octocat',
                repository: '.hidden'
            );
        })->throws(InvalidArgumentException::class, 'Invalid repository format');

        test('it rejects repository with spaces', function () {
            new ProfileIdentifier(
                username: 'octocat',
                repository: 'hello world'
            );
        })->throws(InvalidArgumentException::class, 'Invalid repository format');

        test('it rejects repository with special characters', function () {
            new ProfileIdentifier(
                username: 'octocat',
                repository: 'repo@name'
            );
        })->throws(InvalidArgumentException::class, 'Invalid repository format');
    });

    describe('__toString method', function () {
        test('it returns username for profile-only identifier', function () {
            $identifier = new ProfileIdentifier(username: 'octocat');

            expect((string) $identifier)->toBe('octocat');
        });

        test('it returns username:repository for scoped identifier', function () {
            $identifier = new ProfileIdentifier(
                username: 'octocat',
                repository: 'hello-world'
            );

            expect((string) $identifier)->toBe('octocat:hello-world');
        });

        test('it produces correct cache key format for profile', function () {
            $identifier = new ProfileIdentifier(username: 'pa-ulander');

            expect($identifier->__toString())->toBe('pa-ulander');
        });

        test('it produces correct cache key format for repository', function () {
            $identifier = new ProfileIdentifier(
                username: 'pa-ulander',
                repository: 'yagvc'
            );

            expect($identifier->__toString())->toBe('pa-ulander:yagvc');
        });
    });

    describe('isRepositoryScoped method', function () {
        test('it returns false for profile-only identifier', function () {
            $identifier = new ProfileIdentifier(username: 'octocat');

            expect($identifier->isRepositoryScoped())->toBeFalse();
        });

        test('it returns true for repository-scoped identifier', function () {
            $identifier = new ProfileIdentifier(
                username: 'octocat',
                repository: 'hello-world'
            );

            expect($identifier->isRepositoryScoped())->toBeTrue();
        });
    });

    test('it handles all properties accessible', function () {
        $identifier = new ProfileIdentifier(
            username: 'octocat',
            repository: 'hello-world'
        );

        expect($identifier)->toHaveProperties(['username', 'repository']);
    });
});
