<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProfileViewRequest extends FormRequest
{
    private const MAX_USERNAME_LENGTH = 255;

    private string $userAgent;
    private bool $abbreviated;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'max:' . self::MAX_USERNAME_LENGTH],
            'label' => ['nullable', 'string'],
            'color' => ['nullable', 'string'],
            'style' => ['nullable', 'string'],
            'base' => ['nullable', 'string'],
            'abbreviated' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'A github account username is needed to count views',
        ];
    }

    public function failedValidation(Validator $validator): never
    {
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
        return $this->abbreviated;
    }

    protected function prepareForValidation(): void
    {
        $this->userAgent = $this->header(key: 'User-Agent', default: '');
        $this->abbreviated = $this->boolean(key: 'abbreviated', default: false);
        
        $this->merge(input: [
            'username' => strip_tags(string: $this->input(key: 'username')),
            'label' => strip_tags(string: $this->input(key: 'label')),
            'color' => strip_tags(string: $this->input(key: 'color')),
            'style' => strip_tags(string: $this->input(key: 'style')),
            'base' => strip_tags(string: $this->input(key: 'base')),
        ]);
    }
}