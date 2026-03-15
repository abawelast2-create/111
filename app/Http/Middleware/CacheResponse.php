<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheResponse
{
    public function handle(Request $request, Closure $next, int $minutes = 5)
    {
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        $key = 'response_cache_' . md5($request->fullUrl());

        $cached = Cache::get($key);
        if ($cached) {
            return response()->json($cached['data'], $cached['status'])
                ->header('X-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->status() === 200) {
            Cache::put($key, [
                'data'   => json_decode($response->getContent(), true),
                'status' => $response->status(),
            ], now()->addMinutes($minutes));
        }

        return $response->header('X-Cache', 'MISS');
    }
}
