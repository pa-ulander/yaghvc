<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Base64DataUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        $decodedValue = urldecode($value);

        if (!preg_match('/^data:image\/(png|jpeg|jpg|gif|svg\+xml);base64,/', $decodedValue)) {
            $fail('The :attribute must be a valid base64 data URL for an image.');
            return;
        }

        $base64Data = preg_replace('/^data:image\/(png|jpeg|jpg|gif|svg\+xml);base64,/', '', $decodedValue);

        $normalized = str_replace(' ', '+', $base64Data);
        $normalized = preg_replace('/[\r\n\t]+/', '', $normalized);

        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $normalized)) {
            $fail('The :attribute contains invalid base64 data.');
            return;
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false || strlen($decoded) === 0) {
            $fail('The :attribute contains invalid base64 data.');
            return;
        }
    }
}
