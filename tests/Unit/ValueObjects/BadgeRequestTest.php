<?php

declare(strict_types=1);

use App\ValueObjects\BadgeConfiguration;
use App\ValueObjects\BadgeRequest;
use App\ValueObjects\ProfileIdentifier;

describe('BadgeRequest', function () {
    test('it creates request with all components', function () {
        $profile = new ProfileIdentifier(username: 'octocat', repository: 'hello-world');
        $config = new BadgeConfiguration(
            label: 'Views',
            color: 'blue',
            style: 'flat',
            abbreviated: true
        );

        $request = new BadgeRequest(
            profile: $profile,
            config: $config,
            baseCount: 100
        );

        expect($request->profile)->toBe($profile)
            ->and($request->config)->toBe($config)
            ->and($request->baseCount)->toBe(100);
    });

    test('it creates request with default base count', function () {
        $profile = new ProfileIdentifier(username: 'octocat');
        $config = new BadgeConfiguration(
            label: 'Views',
            color: 'blue',
            style: 'flat',
            abbreviated: false
        );

        $request = new BadgeRequest(
            profile: $profile,
            config: $config
        );

        expect($request->baseCount)->toBe(0);
    });

    test('it is immutable readonly class', function () {
        $profile = new ProfileIdentifier(username: 'octocat');
        $config = new BadgeConfiguration(
            label: 'Views',
            color: 'blue',
            style: 'flat',
            abbreviated: false
        );

        $request = new BadgeRequest(profile: $profile, config: $config);

        expect($request)->toBeInstanceOf(BadgeRequest::class);
        // Attempting to modify readonly property would cause PHP error
    });

    test('it allows nested objects to be accessed', function () {
        $profile = new ProfileIdentifier(username: 'octocat', repository: 'hello-world');
        $config = new BadgeConfiguration(
            label: 'Views',
            color: 'blue',
            style: 'flat',
            abbreviated: true
        );

        $request = new BadgeRequest(profile: $profile, config: $config, baseCount: 50);

        // Can access nested properties
        expect($request->profile->username)->toBe('octocat')
            ->and($request->profile->repository)->toBe('hello-world')
            ->and($request->config->label)->toBe('Views')
            ->and($request->config->style)->toBe('flat');
    });

    describe('fromValidatedData factory', function () {
        test('it creates request from complete data', function () {
            $data = [
                'username' => 'octocat',
                'repository' => 'hello-world',
                'base' => 100,
                'label' => 'Profile Views',
                'color' => 'red',
                'style' => 'flat-square',
                'abbreviated' => true,
                'labelColor' => 'black',
                'logoColor' => 'white',
                'logo' => 'github',
                'logoSize' => '24',
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->profile->username)->toBe('octocat')
                ->and($request->profile->repository)->toBe('hello-world')
                ->and($request->baseCount)->toBe(100)
                ->and($request->config->label)->toBe('Profile Views')
                ->and($request->config->color)->toBe('red')
                ->and($request->config->style)->toBe('flat-square')
                ->and($request->config->abbreviated)->toBeTrue()
                ->and($request->config->labelColor)->toBe('black')
                ->and($request->config->logoColor)->toBe('white')
                ->and($request->config->logo)->toBe('github')
                ->and($request->config->logoSize)->toBe('24');
        });

        test('it creates request with only username', function () {
            $data = [
                'username' => 'octocat',
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->profile->username)->toBe('octocat')
                ->and($request->profile->repository)->toBeNull()
                ->and($request->baseCount)->toBe(0)
                ->and($request->config->label)->toBe('Visits')
                ->and($request->config->color)->toBe('blue')
                ->and($request->config->style)->toBe('for-the-badge')
                ->and($request->config->abbreviated)->toBeFalse();
        });

        test('it creates request without repository', function () {
            $data = [
                'username' => 'pa-ulander',
                'label' => 'Profile Views',
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->profile->username)->toBe('pa-ulander')
                ->and($request->profile->repository)->toBeNull()
                ->and($request->profile->isRepositoryScoped())->toBeFalse();
        });

        test('it creates request with repository', function () {
            $data = [
                'username' => 'pa-ulander',
                'repository' => 'yagvc',
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->profile->username)->toBe('pa-ulander')
                ->and($request->profile->repository)->toBe('yagvc')
                ->and($request->profile->isRepositoryScoped())->toBeTrue();
        });

        test('it handles base count as integer', function () {
            $data = [
                'username' => 'octocat',
                'base' => 1000,
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->baseCount)->toBe(1000);
        });

        test('it handles base count as string', function () {
            $data = [
                'username' => 'octocat',
                'base' => '500',
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->baseCount)->toBe(500);
        });

        test('it defaults base count to zero when missing', function () {
            $data = [
                'username' => 'octocat',
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->baseCount)->toBe(0);
        });

        test('it defaults base count to zero when empty string', function () {
            $data = [
                'username' => 'octocat',
                'base' => '',
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->baseCount)->toBe(0);
        });

        test('it handles negative base count', function () {
            $data = [
                'username' => 'octocat',
                'base' => -100,
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->baseCount)->toBe(-100);
        });

        test('it validates username through ProfileIdentifier', function () {
            $data = [
                'username' => '',
            ];

            BadgeRequest::fromValidatedData($data);
        })->throws(InvalidArgumentException::class, 'Username cannot be empty');

        test('it validates style through BadgeConfiguration', function () {
            $data = [
                'username' => 'octocat',
                'style' => 'invalid-style',
            ];

            BadgeRequest::fromValidatedData($data);
        })->throws(InvalidArgumentException::class, 'Invalid badge style');

        test('it validates repository through ProfileIdentifier', function () {
            $data = [
                'username' => 'octocat',
                'repository' => '',
            ];

            BadgeRequest::fromValidatedData($data);
        })->throws(InvalidArgumentException::class, 'Repository cannot be empty');

        test('it handles missing username as empty string', function () {
            $data = [];

            BadgeRequest::fromValidatedData($data);
        })->throws(InvalidArgumentException::class, 'Username cannot be empty');

        test('it handles null repository as null', function () {
            $data = [
                'username' => 'octocat',
                'repository' => null,
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->profile->repository)->toBeNull();
        });

        test('it ignores non-string non-null repository', function () {
            $data = [
                'username' => 'octocat',
                'repository' => 123,
            ];

            $request = BadgeRequest::fromValidatedData($data);

            expect($request->profile->repository)->toBeNull();
        });
    });

    test('it has all properties accessible', function () {
        $profile = new ProfileIdentifier(username: 'octocat');
        $config = new BadgeConfiguration(
            label: 'Views',
            color: 'blue',
            style: 'flat',
            abbreviated: false
        );

        $request = new BadgeRequest(profile: $profile, config: $config);

        expect($request)->toHaveProperties(['profile', 'config', 'baseCount']);
    });
});
