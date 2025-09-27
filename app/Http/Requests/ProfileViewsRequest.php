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

/** @package App\Http\Requests */
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

    /**
     * Validation rules.
     * @return array<string, mixed>
     */
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
                'max:5000',
            ],
            'logoSize' => ['nullable', 'regex:/^(auto|[0-9]{1,2})$/'],
            'user_agent' => ['required', 'string'],
        ];
    }

    /**
     * Custom validation messages.
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'A GitHub account username is needed to count views',
            'username.regex' => 'The username must be a valid GitHub username',
            'logo.regex' => 'Invalid logo parameter. Provide a simple-icons slug, a base64 image, or a data URI (png|jpeg|gif|svg).',
        ];
    }

    public function failedValidation(Validator $validator): never
    {
        Log::warning(message: 'Validation errors: ' . print_r(value: $this->all(), return: true), context: $validator->errors()->toArray());

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

        $usernameValue = $this->input(key: 'username');
        if (is_string($usernameValue) && $usernameValue !== '') {
            $sanitizedUsername = preg_replace(pattern: '/[^\p{L}\p{N}_-]/u', replacement: '', subject: $usernameValue);
            $mergeData['username'] = trim(string: $sanitizedUsername ?? $usernameValue);

            $optionalFields = ['label', 'color', 'style', 'base', 'repository', 'labelColor', 'logoColor', 'logoSize'];

            foreach ($optionalFields as $field) {
                $rawValue = $this->input(key: $field);
                if ($rawValue === null || ! is_scalar($rawValue)) {
                    continue;
                }
                $cleaned = strip_tags(string: (string) $rawValue);
                $mergeData[$field] = trim(string: $cleaned);
            }

            $logoValue = $this->input(key: 'logo');
            if (is_string($logoValue) && $logoValue !== '') {
                $rawLogo = trim(string: $logoValue);

                if (stripos(haystack: $rawLogo, needle: 'data:image/svg xml;base64,') === 0) {
                    $suffix = (string) substr(string: $rawLogo, offset: strlen(string: 'data:image/svg xml;base64,'));
                    $rawLogo = 'data:image/svg+xml;base64,' . $suffix;
                }

                if (str_starts_with(haystack: strtolower(string: $rawLogo), needle: 'data:image/') && str_contains(haystack: $rawLogo, needle: ';base64,')) {
                    $parts = explode(separator: ';base64,', string: $rawLogo, limit: 2);
                    if (count(value: $parts) === 2) {
                        [$header, $payload] = $parts;
                        if (str_contains(haystack: $payload, needle: ' ')) {
                            $repaired = str_replace(search: ' ', replace: '+', subject: $payload);
                            $trimmed = rtrim(string: $repaired, characters: '=');
                            if ($trimmed !== '' && preg_match(pattern: '/^[A-Za-z0-9+\/]+$/', subject: $trimmed)) {
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
        $logo = $this->input(key: 'logo');
        if (! is_string($logo) || $logo === '') {
            return;
        }
        // Skip if already data URI or slug pattern
        if (preg_match(pattern: '/^(data:image\/|data%3Aimage%2F)/i', subject: $logo) || preg_match(pattern: '/^[a-z0-9-]{1,60}$/i', subject: $logo)) {
            return;
        }
        // Candidate raw/encoded base64 – normalize & attempt decode + mime inference
        $decodedOnce = urldecode(string: $logo);
        // A raw base64 value in a query string may have had '+' interpreted as space. Reconstitute.
        if (str_contains(haystack: $decodedOnce, needle: ' ') && !str_contains(haystack: $decodedOnce, needle: '+')) {
            $decodedOnce = str_replace(search: ' ', replace: '+', subject: $decodedOnce);
        }
        $candidate = preg_replace(pattern: '/\s+/', replacement: '', subject: $decodedOnce) ?? $decodedOnce;
        if ($candidate === '' || !preg_match(pattern: '/^[A-Za-z0-9+\/]+=*$/', subject: $candidate)) {
            $this->failLogo(message: 'Invalid base64 logo payload.');
            return;
        }
        $binary = base64_decode(string: $candidate, strict: true);
        if ($binary === false || $binary === '') {
            $this->failLogo(message: 'Invalid base64 logo payload.');
            return;
        }
        // MIME inference (must match allowed list)
        $mime = LogoDataHelper::inferMime(binary: $binary);
        if ($mime === null) {
            $this->failLogo(message: 'Unsupported or ambiguous logo format.');
            return;
        }
        // Size enforcement (mirror LogoProcessor early constraints)
        $maxBytes = $this->intConfig(key: 'badge.logo_max_bytes', default: 10000);
        if (!LogoDataHelper::withinSize(binary: $binary, maxBytes: $maxBytes)) {
            $this->failLogo(message: 'Logo image exceeds maximum allowed size.');
            return;
        }
        if ($mime === 'svg+xml') {
            if (LogoDataHelper::sanitizeSvg(svg: $binary) === null) {
                $this->failLogo(message: 'Unsafe SVG content rejected.');
                return;
            }
        }
    }

    private function failLogo(string $message): void
    {
        $validator = $this->getValidatorInstance();
        $validator->errors()->add(key: 'logo', message: $message);
        throw new HttpResponseException(response: response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data' => $validator->errors(),
        ], status: 422));
    }

    /**
     * Return all request input including injected user_agent.
     *
     * @param list<string>|string|null $keys
     * @return array<string, mixed>
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
     * Return validated data plus ensured user_agent; optionally a single key.
     *
     * @param array-key|null $key
     * @return array<string,mixed>|mixed
     */
    public function validated(mixed $key = null, mixed $default = null): mixed
    {
        $validated = $this->validator->validated();
        $all = $this->all();
        $merged =  [...$validated, ...['user_agent' => $all['user_agent']]];

        if ($key !== null) {
            return Arr::get(array: $merged, key: $key, default: $default);
        }

        return $merged;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = config(key: $key, default: $default);
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        return $default;
    }
}
