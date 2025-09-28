<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** @package App\Http\Middleware */
class GithubCamoOnly
{
    /**
     * Handle an incoming request.
     * Only allow requests from GitHub's camo-client user agent.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header(key: 'User-Agent');

        $githubCamoOnly = $this->boolConfig('auth.github_camo_only', true);

        // Check if GitHub Camo restriction is disabled entirely
        if (! $githubCamoOnly) {
            return $next($request);
        }

        // Allow GitHub's camo-client user agent
        if ($userAgent === 'camo-client') {
            return $next($request);
        }

        // Allow all user agents if explicitly configured
        if ($this->boolConfig('auth.allow_all_user_agents', false)) {
            return $next($request);
        }

        // Check if current environment is in the exceptions list
        $environmentExceptions = $this->stringListConfig('auth.environment_exceptions', ['local', 'testing']);
        if (in_array(needle: app()->environment(), haystack: $environmentExceptions, strict: true)) {
            return $next($request);
        }

        // Otherwise, block access
        Log::warning(message: 'Unauthorized access attempt', context: [
            'ip' => $request->ip(),
            'user_agent' => $userAgent,
            'path' => $request->path()
        ]);

        return response(content: 'Unauthorized', status: 403);
    }

    private function boolConfig(string $key, bool $default): bool
    {
        $value = config($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            $normalized = strtolower($value);
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        return $default;
    }

    /**
     * @param list<string> $default
     * @return list<string>
     */
    private function stringListConfig(string $key, array $default): array
    {
        $value = config($key, $default);
        if (! is_array($value)) {
            return $default;
        }
        $strings = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }
        if ($strings === []) {
            return $default;
        }

        return $strings;
    }
}
