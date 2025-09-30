<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ProfileViewsController;
use App\Http\Requests\ProfileViewsRequest;
use App\Models\ProfileViews;
use App\Repositories\ProfileViewsRepository;
use App\Services\BadgeRenderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ValidatedInput;

beforeEach(function () {
    $badgeRenderService = app(BadgeRenderService::class);
    $repository = app(ProfileViewsRepository::class);
    $controller = new ProfileViewsController($badgeRenderService, $repository);
    // store in container for retrieval in individual tests
    app()->instance('test.controller', $controller);

    Config::set('badge.default_label', 'Profile Views');
    Config::set('badge.default_color', 'blue');
    Config::set('badge.default_style', 'flat');
    Config::set('badge.default_abbreviated', false);
});

it('returns badge with correct headers', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge(['username' => 'test-user']);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    /** @var ProfileViewsController $controller */
    $controller = app('test.controller');
    $response = $controller->index($request);

    $cacheControl = $response->headers->get('Cache-Control');
    expect($response)
        ->toBeInstanceOf(Response::class)
        ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml')
        ->and($cacheControl)->toContain('max-age=1')
        ->and($cacheControl)->toContain('stale-while-revalidate')
        ->and($response->headers->has('ETag'))->toBeTrue();
});

it('processes request parameters correctly', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge([
        'username' => 'test-user',
        'label' => 'Custom Label',
        'color' => 'red',
        'style' => 'flat-square',
        'base' => 100,
        'repository' => 'my-repo',
        'abbreviated' => true
    ]);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    $controller = app('test.controller');
    $response = $controller->index($request);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toContain('Custom Label');
});

it('applies base value correctly when provided', function () {
    $username = 'base-test-user';
    $profileViews = app(ProfileViewsRepository::class)->findOrCreate($username);

    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge([
        'username' => $username,
        'base' => 100
    ]);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    $controller = app('test.controller');
    $response = $controller->index($request);

    expect($response->getStatusCode())->toBe(200);
});

it('uses default values for optional parameters', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge(['username' => 'test-user']);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    $controller = app('test.controller');
    $response = $controller->index($request);

    expect($response->getStatusCode())->toBe(200);
});

it('can use each allowed style parameter', function () {
    $allowedStyles = ['flat', 'flat-square', 'for-the-badge', 'plastic'];

    foreach ($allowedStyles as $style) {
        $request = new ProfileViewsRequest();
        $request->headers->set('User-Agent', 'TestBrowser');
        $request->merge([
            'username' => 'test-user',
            'style' => $style
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $validator->validate();
        $request->setValidator($validator);

        $controller = app('test.controller');
        $response = $controller->index($request);

        expect($response->getStatusCode())->toBe(200);
    }
});

it('renders badge with correct parameters', function () {
    $controller = app('test.controller');
    $reflectionClass = new \ReflectionClass($controller);
    $renderBadgeMethod = $reflectionClass->getMethod('renderBadge');
    $renderBadgeMethod->setAccessible(true);

    $safe = [
        'username' => 'test-user',
        'label' => 'Profile Views', // Use a label that exists in the badge
        'color' => 'red',
        'style' => 'for-the-badge',
        'abbreviated' => true
    ];

    $badgeRequest = \App\ValueObjects\BadgeRequest::fromValidatedData($safe);
    $profileViews = app(ProfileViewsRepository::class)->findOrCreate('test-user');

    $svgContent = $renderBadgeMethod->invoke($controller, $badgeRequest, $profileViews);

    expect($svgContent)->toBeString()
        ->and($svgContent)->toContain('<svg')
        ->and($svgContent)->toContain('Profile Views')
        ->and($svgContent)->toContain('</svg>');
});

it('creates response with correct headers', function () {
    $controller = app('test.controller');
    $reflectionClass = new \ReflectionClass($controller);
    $createResponseMethod = $reflectionClass->getMethod('createBadgeResponse');
    $createResponseMethod->setAccessible(true);

    $svgContent = '<svg>Test SVG</svg>';

    $response = $createResponseMethod->invoke(
        $controller,
        $svgContent
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getContent())->toBe('<svg>Test SVG</svg>')
        ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml');
});

it('handles repository parameter correctly', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge([
        'username' => 'test-user',
        'repository' => 'my-awesome-repo'
    ]);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    $controller = app('test.controller');
    $response = $controller->index($request);

    expect($response->getStatusCode())->toBe(200);
});

it('creates separate counts for profile and repository views', function () {
    $username = 'counter-test-user';

    // Create profile view
    $profileRequest = new ProfileViewsRequest();
    $profileRequest->headers->set('User-Agent', 'TestBrowser');
    $profileRequest->merge(['username' => $username]);

    $validator = Validator::make($profileRequest->all(), $profileRequest->rules());
    $validator->validate();
    $profileRequest->setValidator($validator);

    $controller = app('test.controller');
    $controller->index($profileRequest); // Count: 1

    // Create repository view
    $repoRequest = new ProfileViewsRequest();
    $repoRequest->headers->set('User-Agent', 'TestBrowser');
    $repoRequest->merge(['username' => $username, 'repository' => 'test-repo']);

    $validator = Validator::make($repoRequest->all(), $repoRequest->rules());
    $validator->validate();
    $repoRequest->setValidator($validator);

    $controller->index($repoRequest); // Count: 1 (separate from profile)

    // Increment profile view again
    $controller->index($profileRequest); // Count: 2

    // Check that counts are separate
    $profileView = ProfileViews::where('username', $username)->whereNull('repository')->first();
    $repoView = ProfileViews::where('username', $username)->where('repository', 'test-repo')->first();

    expect($profileView->visit_count)->toBe(2);
    expect($repoView->visit_count)->toBe(1);
});

it('handles multiple repositories for same user', function () {
    $username = 'multi-repo-user';

    // Create views for different repositories
    $repos = ['repo1', 'repo2', 'repo3'];

    foreach ($repos as $repo) {
        $request = new ProfileViewsRequest();
        $request->headers->set('User-Agent', 'TestBrowser');
        $request->merge(['username' => $username, 'repository' => $repo]);

        $validator = Validator::make($request->all(), $request->rules());
        $validator->validate();
        $request->setValidator($validator);

        $controller = app('test.controller');
        $controller->index($request);
    }

    // Check that each repository has its own count
    foreach ($repos as $repo) {
        $repoView = ProfileViews::where('username', $username)->where('repository', $repo)->first();
        expect($repoView->visit_count)->toBe(1);
    }
});

it('returns 304 when if-none-match header matches badge etag', function () {
    $controller = app('test.controller');
    $method = new \ReflectionMethod(ProfileViewsController::class, 'createBadgeResponse');
    $method->setAccessible(true);

    $badge = '<svg>etag-me</svg>';
    $etag = 'W/"' . sha1($badge) . '"';
    $originalHeader = request()->headers->get('If-None-Match');

    try {
        request()->headers->set('If-None-Match', $etag);

        $response = $method->invoke($controller, $badge);

        expect($response->getStatusCode())->toBe(304)
            ->and($response->getContent())->toBe('');
    } finally {
        if ($originalHeader === null) {
            request()->headers->remove('If-None-Match');
        } else {
            request()->headers->set('If-None-Match', $originalHeader);
        }
    }
});

it('normalises validated input and arrays consistently', function () {
    $controller = app('test.controller');
    $reflection = new \ReflectionMethod(ProfileViewsController::class, 'normaliseValidatedInput');
    $reflection->setAccessible(true);

    $validated = new ValidatedInput(['foo' => 'bar']);
    $asArray = $reflection->invoke($controller, $validated);
    $directArray = $reflection->invoke($controller, ['baz' => 'qux']);

    expect($asArray)->toBe(['foo' => 'bar'])
        ->and($directArray)->toBe(['baz' => 'qux']);
});

// Removed: stringFromSafe logic is now in BadgeConfiguration value object
// See tests/Unit/ValueObjects/BadgeConfigurationTest.php for comprehensive tests

// Removed: boolFromSafe logic is now in BadgeConfiguration value object
// See tests/Unit/ValueObjects/BadgeConfigurationTest.php for comprehensive tests

// Removed: stringConfig and boolConfig logic is now in BadgeConfiguration value object
// See tests/Unit/ValueObjects/BadgeConfigurationTest.php for comprehensive tests

it('pulls query strings safely', function () {
    $controller = app('test.controller');
    $method = new \ReflectionMethod(ProfileViewsController::class, 'queryString');
    $method->setAccessible(true);

    $originalQuery = request()->query->all();

    try {
        request()->query->replace([
            'label' => 'views',
            'multi' => ['a', 'b'],
        ]);

        expect($method->invoke($controller, 'label'))->toBe('views')
            ->and($method->invoke($controller, 'multi'))->toBeNull()
            ->and($method->invoke($controller, 'missing'))->toBeNull();
    } finally {
        request()->query->replace($originalQuery);
    }
});
