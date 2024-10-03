<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ProfileViewRequest extends FormRequest
{
    private string $userAgent;
    private bool $isCountAbbreviated;

    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'username' => 'required|max:255',
            'label' => 'nullable|string',
            'color' => 'nullable|string',
            'style' => 'nullable|string',
            'base' => 'nullable|string',
            'abbreviated' => 'boolean',
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
        throw new HttpResponseException(response()->json([
            'success'   => false,
            'message'   => 'Validation errors',
            'data'      => $validator->errors()
        ], 422));
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getUsername(): string
    {
        return $this->input('username');
    }

    public function getBadgeLabel(): ?string
    {
        return $this->input('label');
    }

    public function getBadgeColor(): ?string
    {
        return $this->input('color');
    }

    public function getBadgeStyle(): ?string
    {
        return $this->input('style');
    }

    public function getBaseCount(): ?string
    {
        return $this->input('base');
    }

    public function isCountAbbreviated(): bool
    {
        return $this->isCountAbbreviated;
    }

    protected function prepareForValidation(): void
    {
        $this->userAgent = $this->header('User-Agent', '');
        $this->isCountAbbreviated = $this->boolean('abbreviated', false);
    }
}
