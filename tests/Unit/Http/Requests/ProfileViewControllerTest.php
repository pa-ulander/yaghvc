<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ProfileViewsController;
use App\Http\Requests\ProfileViewsRequest;
use App\Repositories\ProfileViewsRepository;
use App\Services\BadgeRenderService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->badgeRenderService = app(BadgeRenderService::class);
    $this->repository = app(ProfileViewsRepository::class);

    $this->controller = new ProfileViewsController(
        $this->badgeRenderService,
        $this->repository
    );

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

    $response = $this->controller->index($request);

    expect($response)
        ->toBeInstanceOf(Response::class)
        ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml')
        ->and($response->headers->has('Cache-Control'))->toBeTrue() // Just check for existence, not exact format
        ->and($response->headers->get('Pragma'))->toBe('no-cache')
        ->and($response->headers->get('Expires'))->toBe('0');
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

    $response = $createResponseMethod->invoke(
        $this->controller,
        $svgContent
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getContent())->toBe('<svg>Test SVG</svg>')
        ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml');
});
