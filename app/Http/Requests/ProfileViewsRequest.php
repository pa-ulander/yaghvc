<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProfileViewsRequest extends FormRequest
{
    private const int MAX_USERNAME_LENGTH = 39;

    private const array ALLOWED_STYLES = ['flat', 'flat-square', 'for-the-badge', 'plastic'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'max:' . self::MAX_USERNAME_LENGTH,
                'regex:/^[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}$/i'
            ],
            'label' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'regex:/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^[a-zA-Z]+$/'],
            'style' => ['nullable', 'string', Rule::in(values: self::ALLOWED_STYLES)],
            'base' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'abbreviated' => ['nullable', 'boolean'],
            'user_agent' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'A GitHub account username is needed to count views',
            'username.regex' => 'The username must be a valid GitHub username',
        ];
    }

    public function failedValidation(Validator $validator): never
    {
        Log::warning(message: 'Validation errors: ', context: $validator->errors()->toArray());

        throw new HttpResponseException(response: response()->json(data: [
            'success' => false,
            'message' => 'Validation errors',
            'data' => $validator->errors(),
            'input' => print_r($this->all(), true),
        ], status: 422));
    }

    protected function prepareForValidation(): void
    {
        $mergeData = [
            'user_agent' => $this->header(key: 'User-Agent', default: ''),
        ];

        if ($this->has('username') && !empty($this->input(key: 'username'))) {
            $mergeData['username'] = trim(string: preg_replace(pattern: '/[^\p{L}\p{N}_-]/u', replacement: '', subject: $this->input(key: 'username')));

            $optionalFields = ['label', 'color', 'style', 'base'];

            foreach ($optionalFields as $field) {
                if ($this->input(key: $field) === null) {
                    continue;
                }
                if ($this->has($field)) {
                    $mergeData[$field] = trim(string: strip_tags(string: $this->input(key: $field)));
                }
            }

            if ($this->has(key: 'abbreviated')) {
                $mergeData['abbreviated'] = $this->boolean(key: 'abbreviated');
            }
        }

        $this->merge(input: $mergeData);
    }

    /**
     * @param array|mixed|null $keys
     * @return array
     */
    public function all(mixed $keys = null): array
    {
        $data = parent::all(keys: $keys);
        if (!isset($data['user_agent'])) {
            $data['user_agent'] = $this->header(key: 'User-Agent', default: '');
        }
        return $data;
    }

    // protected function passedValidation(): void
    // {
    //     dump('passedValidation method called');
    // }


    /**
     * @param array|int|string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function validated(mixed $key = null, mixed $default = null): mixed
    {
        $validated = $this->validator->validated();
        $all = $this->all();

        $merged = array_merge($validated, ['user_agent' => $all['user_agent']]);

        return $key ? Arr::get(array: $merged, key: $key, default: $default) : $merged;
    }
}
