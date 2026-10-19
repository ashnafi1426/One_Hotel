<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');

        $allowedOrigins = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://localhost:5174',
            'http://127.0.0.1:5174',
            'https://one-hotel-lemon.vercel.app',
        ];

        $frontendUrl = config('app.frontend_url', env('APP_FRONTEND_URL'));
        if ($frontendUrl && !in_array($frontendUrl, $allowedOrigins)) {
            $allowedOrigins[] = rtrim($frontendUrl, '/');
        }

        $isAllowed = in_array($origin, $allowedOrigins) || (is_string($origin) && preg_match('/\.vercel\.app$/', parse_url($origin, PHP_URL_HOST) ?? $origin));
        $requestedHeaders = $request->header('Access-Control-Request-Headers')
            ?: 'Content-Type, Authorization, Accept, X-Requested-With, X-Hotel-ID, x-hotel-id, Origin';
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            if ($isAllowed) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', $requestedHeaders);
                $response->headers->set('Access-Control-Max-Age', '86400');
            }

            return $response;
        }

        $response = $next($request);

        if ($isAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', $requestedHeaders);
        }
        return $response;
    }
}

