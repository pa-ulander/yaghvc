<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProfileViewRequest extends FormRequest
{
    private const MAX_USERNAME_LENGTH = 39; // GitHub's max username length

    private const ALLOWED_STYLES = ['flat', 'flat-square', 'for-the-badge', 'plastic']; // example styles

    private string $userAgent;
    private bool $abbreviated;

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
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^[a-zA-Z]+$/'],
            'style' => ['nullable', 'string', Rule::in(values: self::ALLOWED_STYLES)],
            'base' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'abbreviated' => ['nullable', 'boolean'],
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
        ], status: 422));
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getUsername(): string
    {
        return $this->input(key: 'username');
    }

    public function getBadgeLabel(): ?string
    {
        return $this->input(key: 'label');
    }

    public function getBadgeColor(): ?string
    {
        return $this->input(key: 'color');
    }

    public function getBadgeStyle(): ?string
    {
        return $this->input(key: 'style');
    }

    public function getBaseCount(): ?string
    {
        return $this->input(key: 'base');
    }

    public function getAbbreviated(): bool
    {
        return $this->input('abbreviated', false);
    }

    protected function prepareForValidation(): void
    {
        $this->userAgent = $this->header(key: 'User-Agent', default: '');

        if ($this->has('username') && !empty($this->input(key: 'username'))) {
            $mergeData = [
                'username' => trim(string: preg_replace(pattern: '/[^\p{L}\p{N}_-]/u', replacement: '', subject: $this->input(key: 'username'))),
            ];
            $optionalFields = ['label', 'color', 'style', 'base'];

            foreach ($optionalFields as $field) {
                if ($this->has($field)) {
                    $mergeData[$field] = trim(string: strip_tags(string: $this->input(key: $field)));
                }
            }

            if ($this->has('abbreviated')) {
                $mergeData['abbreviated'] = $this->boolean('abbreviated');
            }

            $this->merge(input: $mergeData);
        }
    }
}
