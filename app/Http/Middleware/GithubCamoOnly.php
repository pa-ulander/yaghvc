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

        // Allow GitHub's camo-client user agent or configure exceptions in .env
        if (
            $userAgent === 'camo-client' ||
            (env('ALLOW_ALL_USER_AGENTS', false) && app()->environment(['local', 'testing']))
        ) {
            return $next($request);
        }

        // Log unauthorized access attempts 
        Log::warning('Unauthorized access attempt', [
            'ip' => $request->ip(),
            'user_agent' => $userAgent,
            'path' => $request->path()
        ]);

        // Return a simple and generic error message
        return response('Unauthorized', 403);
    }
}
