<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    public function handle(Request $request, Closure $next, int $maxRequests = 60, string $prefix = 'api')
    {
        $ip = $request->ip();
        $key = "rate_limit:{$prefix}:{$ip}";
        $blockKey = "rate_block:{$prefix}:{$ip}";

        if (Cache::get($blockKey)) {
            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز الحد المسموح من الطلبات. حاول مرة أخرى بعد دقيقة.',
            ], 429)->header('Retry-After', 60);
        }

        $requests = Cache::get($key, 0);

        if ($requests >= $maxRequests) {
            Cache::put($blockKey, true, 60);
            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز الحد المسموح من الطلبات. حاول مرة أخرى بعد دقيقة.',
            ], 429)->header('Retry-After', 60);
        }

        Cache::put($key, $requests + 1, 60);

        return $next($request);
    }
}
