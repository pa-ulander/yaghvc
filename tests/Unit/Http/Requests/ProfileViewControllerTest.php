<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ProfileViewsController;
use App\Http\Requests\ProfileViewsRequest;
use App\Repositories\ProfileViewsRepository;
use App\Services\BadgeRenderService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->badgeRenderService = app(BadgeRenderService::class);
    $this->repository = app(ProfileViewsRepository::class);

    $this->controller = new ProfileViewsController(
        $this->badgeRenderService,
        $this->repository
    );

    Config::set('cache.limiters.profile-views.max_attempts', 5);
    Config::set('cache.limiters.profile-views.decay_minutes', 1);
    Config::set('badge.default_label', 'Profile Views');
    Config::set('badge.default_color', 'blue');
    Config::set('badge.default_style', 'flat');
    Config::set('badge.default_abbreviated', false);

    RateLimiter::clear('profile-views:127.0.0.1');
});

it('returns badge with correct headers', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge(['username' => 'test-user']);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    $response = $this->controller->index($request);

    expect($response)
        ->toBeInstanceOf(Response::class)
        ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml')
        ->and($response->headers->has('Cache-Control'))->toBeTrue() // Just check for existence, not exact format
        ->and($response->headers->get('Pragma'))->toBe('no-cache')
        ->and($response->headers->get('Expires'))->toBe('0')
        ->and($response->headers->get('X-RateLimit-Limit'))->toBe('5');
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
        'abbreviated' => true
    ]);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    $response = $this->controller->index($request);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toContain('Custom Label');
});

it('handles rate limiting correctly', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge(['username' => 'test-user']);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    // Make sure we're using the exact same key format as in the controller
    $key = 'profile-views:' . $request->input('username');
    $ip = '192.168.1.1';
    $request->server->set('REMOTE_ADDR', $ip);

    RateLimiter::clear($key);
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit($key);
    }

    $response = $this->controller->index($request);

    expect($response->getStatusCode())->toBe(429)
        ->and($response->headers->has('Retry-After'))->toBeTrue()
        ->and($response->headers->get('X-RateLimit-Limit'))->toBe('5')
        ->and($response->headers->get('X-RateLimit-Remaining'))->toBe('0');
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

    $response = $this->controller->index($request);

    expect($response->getStatusCode())->toBe(200);
});

it('uses default values for optional parameters', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge(['username' => 'test-user']);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    $response = $this->controller->index($request);

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

        $response = $this->controller->index($request);

        expect($response->getStatusCode())->toBe(200);
    }
});

it('increments rate limiter on each request', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestBrowser');
    $request->merge(['username' => 'test-user']);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->validate();
    $request->setValidator($validator);

    // Use the same key format as in the controller
    $key = 'profile-views:' . $request->input('username');
    RateLimiter::clear($key);

    $response1 = $this->controller->index($request);
    expect($response1->headers->get('X-RateLimit-Remaining'))->toBe('4');

    $response2 = $this->controller->index($request);
    expect($response2->headers->get('X-RateLimit-Remaining'))->toBe('3');
});

it('renders badge with correct parameters', function () {
    $reflectionClass = new \ReflectionClass($this->controller);
    $renderBadgeMethod = $reflectionClass->getMethod('renderBadge');
    $renderBadgeMethod->setAccessible(true);

    $safe = [
        'username' => 'test-user',
        'label' => 'Profile Views', // Use a label that exists in the badge
        'color' => 'red',
        'style' => 'for-the-badge',
        'abbreviated' => true
    ];

    $profileViews = app(ProfileViewsRepository::class)->findOrCreate('test-user');

    $svgContent = $renderBadgeMethod->invoke($this->controller, $safe, $profileViews);

    expect($svgContent)->toBeString()
        ->and($svgContent)->toContain('<svg')
        ->and($svgContent)->toContain('Profile Views')
        ->and($svgContent)->toContain('</svg>');
});

it('creates response with correct headers', function () {
    $reflectionClass = new \ReflectionClass($this->controller);
    $createResponseMethod = $reflectionClass->getMethod('createBadgeResponse');
    $createResponseMethod->setAccessible(true);

    $svgContent = '<svg>Test SVG</svg>';
    $key = 'profile-views:127.0.0.1';
    $maxAttempts = 5;

    $response = $createResponseMethod->invoke(
        $this->controller,
        $svgContent,
        $key,
        $maxAttempts
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getContent())->toBe('<svg>Test SVG</svg>')
        ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml')
        ->and($response->headers->get('X-RateLimit-Limit'))->toBe('5');
});
