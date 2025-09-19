<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\LogoDataHelper;

class ProfileViewsRequest extends FormRequest
{
    private const int MAX_USERNAME_LENGTH = 39;

    private const int MAX_REPOSITORY_NAME_LENGTH = 100;

    private const array ALLOWED_STYLES = ['flat', 'flat-square', 'for-the-badge', 'plastic'];

    /**
     * Unified validation pattern for the `logo` query parameter. Accepts:
     *  - simple-icons slug: [a-z0-9-]{1,60}
     *  - raw data URI: data:image/<png|jpeg|jpg|gif|svg+xml>;base64,<payload>
     *  - percent-encoded data URI starting with data%3Aimage%2F...
     *  - raw base64 (>=24) that may include spaces (treated as '+')
     *  - urlencoded base64 (>=32) possibly containing spaces
     */
    private const string LOGO_REGEX = '/^('
        . '(data:image\/(png|jpeg|jpg|gif|svg\+xml);base64,[A-Za-z0-9+\/%=]+)'
        . '|(data%3Aimage%2F(png|jpeg|jpg|gif|svg%2Bxml)%3Bbase64%2C[A-Za-z0-9%]+)'
        . '|([A-Za-z0-9+\/= ]{24,})'
        . '|([A-Za-z0-9% ]{32,})'
        . '|([a-z0-9-]{1,60})'
        . ')$/i';

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
                'regex:/^[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}$/i',
            ],
            'label' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'regex:/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^[a-zA-Z]+$/'],
            'style' => ['nullable', 'string', Rule::in(values: self::ALLOWED_STYLES)],
            'base' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'repository' => ['nullable', 'string', 'max:' . self::MAX_REPOSITORY_NAME_LENGTH],
            'abbreviated' => ['nullable', 'boolean'],
            'labelColor' => ['nullable', 'regex:/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^[a-zA-Z]+$/'],
            'logoColor' => ['nullable', 'regex:/^([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$|^[a-zA-Z]+$/'],
            // logo can be ONE of:
            //  1) simple-icons slug: [a-z0-9-]{1,60}
            //  2) raw data URI: data:image/<mime>;base64,<payload>
            //  3) percent-encoded data URI starting with data%3Aimage%2F
            //  4) raw base64 payload (we'll attempt mime sniffing later) of reasonable length
            //  5) URL-encoded base64 (percent sequences inside)
            // We keep a conservative upper length bound to avoid very large query strings.
            'logo' => [
                'nullable',
                'regex:' . self::LOGO_REGEX,
                'max:5000', // preserve previous semantics
            ],
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

        if ($this->has('username') && ! empty($this->input(key: 'username'))) {
            $mergeData['username'] = trim(string: preg_replace(pattern: '/[^\p{L}\p{N}_-]/u', replacement: '', subject: $this->input(key: 'username')));

            $optionalFields = ['label', 'color', 'style', 'base', 'repository', 'labelColor', 'logoColor', 'logoSize'];

            foreach ($optionalFields as $field) {
                if ($this->input(key: $field) === null) {
                    continue;
                }
                if ($this->has($field)) {
                    $mergeData[$field] = trim(string: strip_tags(string: $this->input(key: $field)));
                }
            }

            if ($this->has('logo') && $this->input(key: 'logo') !== null) {
                $rawLogo = trim(string: $this->input(key: 'logo'));
                // Edge repair: Some clients incorrectly place an unencoded data URI directly in the query string.
                // In that scenario, '+' characters inside the base64 section may be transformed into spaces by
                // application/x-www-form-urlencoded semantics before reaching Laravel, causing validation to fail
                // (our regex expects '+' not spaces). We attempt a safe, minimal repair: if the value starts with
                // 'data:image/' and contains a ';base64,' marker, and the payload has spaces but no '+', we swap
                // spaces back to '+', but ONLY if doing so yields a syntactically valid base64 fragment. This keeps
                // behaviour strict while providing a graceful acceptance path for this edge case.
                if (str_starts_with(strtolower($rawLogo), 'data:image/') && str_contains($rawLogo, ';base64,')) {
                    $parts = explode(';base64,', $rawLogo, 2);
                    if (count($parts) === 2) {
                        [$header, $payload] = $parts;
                        $spaceCount = substr_count($payload, ' ');
                        // Heuristic: only attempt repair if there are multiple spaces (>=3) and no '+' already.
                        // A single stray space is treated as a genuine malformed data URI and will be rejected
                        // by validation, preserving previous contract in BadgeRenderingTest.
                        if ($spaceCount >= 3 && !str_contains($payload, '+')) {
                            $repaired = str_replace(' ', '+', $payload);
                            // Validate repaired fragment shape before accepting.
                            if (preg_match('/^[A-Za-z0-9+\/]+=*$/', rtrim($repaired, '='))) {
                                $rawLogo = $header . ';base64,' . $repaired;
                            }
                        }
                    }
                }
                $mergeData['logo'] = $rawLogo;
            }

            if ($this->has(key: 'abbreviated')) {
                $mergeData['abbreviated'] = $this->boolean(key: 'abbreviated');
            }
        }

        $this->merge(input: $mergeData);
    }

    protected function passedValidation(): void
    {
        // Additional semantic validation for raw / urlencoded base64 logos that are not yet data URIs or slugs.
        $logo = $this->input('logo');
        if ($logo === null || $logo === '') {
            return;
        }
        // Skip if already data URI or slug pattern
        if (preg_match('/^(data:image\/|data%3Aimage%2F)/i', $logo) || preg_match('/^[a-z0-9-]{1,60}$/i', $logo)) {
            return;
        }
        // Candidate raw/encoded base64 – normalize & attempt decode + mime inference
        $decodedOnce = urldecode($logo);
        // A raw base64 value in a query string may have had '+' interpreted as space. Reconstitute.
        if (str_contains($decodedOnce, ' ') && !str_contains($decodedOnce, '+')) {
            $decodedOnce = str_replace(' ', '+', $decodedOnce);
        }
        $candidate = preg_replace('/\s+/', '', $decodedOnce) ?? '';
        if ($candidate === '' || !preg_match('/^[A-Za-z0-9+\/]+=*$/', $candidate)) {
            $this->failLogo('Invalid base64 logo payload.');
            return;
        }
        $binary = base64_decode($candidate, true);
        if ($binary === false || $binary === '') {
            $this->failLogo('Invalid base64 logo payload.');
            return;
        }
        // MIME inference (must match allowed list)
        $mime = LogoDataHelper::inferMime($binary);
        if ($mime === null) {
            $this->failLogo('Unsupported or ambiguous logo format.');
            return;
        }
        // Size enforcement (mirror LogoProcessor early constraints)
        $maxBytes = (int) config('badge.logo_max_bytes', 10000);
        if (!LogoDataHelper::withinSize($binary, $maxBytes)) {
            $this->failLogo('Logo image exceeds maximum allowed size.');
            return;
        }
        if ($mime === 'svg+xml') {
            if (LogoDataHelper::sanitizeSvg($binary) === null) {
                $this->failLogo('Unsafe SVG content rejected.');
                return;
            }
        }
    }

    private function failLogo(string $message): void
    {
        $validator = $this->getValidatorInstance();
        $validator->errors()->add('logo', $message);
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data' => $validator->errors(),
        ], 422));
    }

    // (inline mime inference moved to LogoDataHelper for DRY)

    /**
     * @param  array|mixed|null  $keys
     */
    public function all(mixed $keys = null): array
    {
        $data = parent::all(keys: $keys);
        if (! isset($data['user_agent'])) {
            $data['user_agent'] = $this->header(key: 'User-Agent', default: '');
        }

        return $data;
    }

    /**
     * @param  array|int|string|null  $key
     */
    public function validated(mixed $key = null, mixed $default = null): mixed
    {
        $validated = $this->validator->validated();
        $all = $this->all();

        $merged = array_merge($validated, ['user_agent' => $all['user_agent']]);

        return $key ? Arr::get(array: $merged, key: $key, default: $default) : $merged;
    }
}
