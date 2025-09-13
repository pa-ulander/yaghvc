<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Rules\Base64DataUrl;
use Illuminate\Validation\Rule;

class ProfileViewsRequest extends FormRequest
{
    private const int MAX_USERNAME_LENGTH = 39;
    private const int MAX_REPOSITORY_NAME_LENGTH = 100;

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
            'repository' => ['nullable', 'string', 'max:' . self::MAX_REPOSITORY_NAME_LENGTH],
            'abbreviated' => ['nullable', 'boolean'],
            'labelColor' => ['nullable', 'regex:/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^[a-zA-Z]+$/'],
            // Accept either a simple-icons slug OR a fully percent-encoded data URI (no raw spaces or % remnants inside base64). Users must encode externally.
            // Accept already percent-encoded data URI (starting with data%3Aimage%2F...) or plain form.
            'logo' => ['nullable', 'regex:/^((data:image\/(png|jpeg|jpg|gif|svg\+xml);base64,[A-Za-z0-9+\/=%]+)|(data%3Aimage%2F(png|jpeg|jpg|gif|svg%2Bxml)%3Bbase64%2C[A-Za-z0-9%]+)|[a-z0-9-]{1,60})$/i', 'max:5000'],
            'logoSize' => ['nullable', 'regex:/^(auto|[0-9]{1,2})$/'],
            'user_agent' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'A GitHub account username is needed to count views',
            'username.regex' => 'The username must be a valid GitHub username',
            'logo.regex' => 'Invalid logo parameter. If using a data URI you must percent-encode the entire value (e.g. encodeURIComponent or rawurlencode).',
        ];
    }

    public function failedValidation(Validator $validator): never
    {
        Log::warning(message: 'Validation errors: ' . print_r($this->all(), true), context: $validator->errors()->toArray());

        throw new HttpResponseException(response: response()->json(data: [
            'success' => false,
            'message' => 'Validation errors',
            'data' => $validator->errors(),
        ], status: 422));
    }

    protected function prepareForValidation(): void
    {
        $mergeData = [
            'user_agent' => $this->header(key: 'User-Agent', default: ''),
        ];

        if ($this->has('username') && !empty($this->input(key: 'username'))) {
            $mergeData['username'] = trim(string: preg_replace(pattern: '/[^\p{L}\p{N}_-]/u', replacement: '', subject: $this->input(key: 'username')));

            $optionalFields = ['label', 'color', 'style', 'base', 'repository', 'labelColor', 'logoSize'];

            foreach ($optionalFields as $field) {
                if ($this->input(key: $field) === null) {
                    continue;
                }
                if ($this->has($field)) {
                    $mergeData[$field] = trim(string: strip_tags(string: $this->input(key: $field)));
                }
            }

            if ($this->has('logo') && $this->input(key: 'logo') !== null) {
                $mergeData['logo'] = trim(string: $this->input(key: 'logo'));
            }

            if ($this->has(key: 'abbreviated')) {
                $mergeData['abbreviated'] = $this->boolean(key: 'abbreviated');
            }
        }

        $this->merge(input: $mergeData);
    }

    /**
     * @param array|mixed|null $keys
     */
    public function all(mixed $keys = null): array
    {
        $data = parent::all(keys: $keys);
        if (!isset($data['user_agent'])) {
            $data['user_agent'] = $this->header(key: 'User-Agent', default: '');
        }
        return $data;
    }

    /**
     * @param array|int|string|null $key
     * @param mixed $default
     */
    public function validated(mixed $key = null, mixed $default = null): mixed
    {
        $validated = $this->validator->validated();
        $all = $this->all();

        $merged = array_merge($validated, ['user_agent' => $all['user_agent']]);

        return $key ? Arr::get(array: $merged, key: $key, default: $default) : $merged;
    }
}
