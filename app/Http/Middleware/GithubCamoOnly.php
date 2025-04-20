<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

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
        $userAgent = $request->header('User-Agent');

        // Check if GitHub Camo restriction is disabled entirely
        if (!config('auth.github_camo_only', true)) {
            return $next($request);
        }

        // Allow GitHub's camo-client user agent
        if ($userAgent === 'camo-client') {
            return $next($request);
        }

        // Allow all user agents if explicitly configured
        if (config('auth.allow_all_user_agents', false)) {
            return $next($request);
        }

        // Check if current environment is in the exceptions list
        $environmentExceptions = config('auth.environment_exceptions', ['local', 'testing']);
        if (in_array(app()->environment(), $environmentExceptions, true)) {
            return $next($request);
        }

        // Otherwise, block access
        Log::warning('Unauthorized access attempt', [
            'ip' => $request->ip(),
            'user_agent' => $userAgent,
            'path' => $request->path()
        ]);

        return response('Unauthorized', 403);
    }
}
