<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ProfileViewsRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->request = new ProfileViewsRequest();
});

it('authorizes all requests', function () {
    expect($this->request->authorize())->toBeTrue();
});

it('has correct validation rules', function () {
    $rules = $this->request->rules();

    expect($rules)->toHaveKeys(['username', 'label', 'color', 'style', 'base', 'abbreviated', 'user_agent']);
    expect($rules['username'])->toContain('required', 'max:39', 'regex:/^[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}$/i');
    expect($rules['label'])->toContain('nullable', 'string', 'max:50');
    expect($rules['color'])->toContain('nullable', 'regex:/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^[a-zA-Z]+$/');
    expect($rules['style'])->toContain('nullable', 'string');
    expect($rules['base'])->toContain('nullable', 'integer', 'min:0', 'max:1000000');
    expect($rules['abbreviated'])->toContain('nullable', 'boolean');
    expect($rules['user_agent'])->toContain('required', 'string');
});

it('has correct error messages', function () {
    $messages = $this->request->messages();

    expect($messages['username.required'])->toBe('A GitHub account username is needed to count views');
    expect($messages['username.regex'])->toBe('The username must be a valid GitHub username');
});

it('throws HttpResponseException on failed validation', function () {
    $validator = Validator::make([], $this->request->rules());
    $validator->fails();

    expect(fn() => $this->request->failedValidation($validator))
        ->toThrow(HttpResponseException::class);
});

it('prepares data for validation', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestAgent');

    $request->merge([
        'username' => ' test-user123@ ',
        'label' => ' <script>Test Label</script> ',
        'color' => ' FF5500 ',
        'style' => ' flat-square ',
        'base' => ' 100 ',
        'abbreviated' => 'true',
    ]);

    // This will trigger the prepareForValidation method
    $request->setContainer(app())->validateResolved();

    $data = $request->all();

    expect($data)
        ->toHaveKey('username', 'test-user123')
        ->toHaveKey('label', 'Test Label')
        ->toHaveKey('color', 'FF5500')
        ->toHaveKey('style', 'flat-square')
        ->toHaveKey('base', '100')
        ->toHaveKey('abbreviated', true)
        ->toHaveKey('user_agent', 'TestAgent');
});

it('includes user_agent in all method', function () {
    $request = new ProfileViewsRequest();
    $request->headers->set('User-Agent', 'TestAgent');

    $all = $request->all();

    expect($all)->toHaveKey('user_agent');
    expect($all['user_agent'])->toBe('TestAgent');
});

it('returns correct validated data', function () {
    $request = new ProfileViewsRequest();
    $request->merge([
        'username' => 'test-user',
        'label' => 'Test Label',
        'user_agent' => 'TestAgent',
    ]);

    $validator = Validator::make($request->all(), $request->rules());
    $request->setValidator($validator);

    $validated = $request->validated();

    expect($validated)
        ->toHaveKeys(['username', 'label', 'user_agent'])
        ->toHaveKey('username', 'test-user')
        ->toHaveKey('label', 'Test Label')
        ->toHaveKey('user_agent', 'TestAgent');
});

it('returns specific validated field', function () {
    $request = new ProfileViewsRequest();
    $request->merge([
        'username' => 'test-user',
        'label' => 'Test Label',
        'user_agent' => 'TestAgent',
    ]);

    $validator = Validator::make($request->all(), $request->rules());
    $request->setValidator($validator);

    expect($request->validated('username'))->toBe('test-user');
    expect($request->validated('non_existent', 'default'))->toBe('default');
});

it('validates allowed styles', function () {
    $request = new ProfileViewsRequest();
    $rules = $request->rules();

    $allowedStyles = ['flat', 'flat-square', 'for-the-badge', 'plastic'];

    foreach ($allowedStyles as $style) {
        $data = ['style' => $style];
        $validator = Validator::make($data, ['style' => $rules['style']]);
        expect($validator->passes())->toBeTrue();
    }

    $data = ['style' => 'invalid-style'];
    $validator = Validator::make($data, ['style' => $rules['style']]);
    expect($validator->fails())->toBeTrue();
});
