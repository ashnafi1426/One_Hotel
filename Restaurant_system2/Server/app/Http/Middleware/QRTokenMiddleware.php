<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\QRResolutionService;
use App\Services\TenantContext;
use App\Models\Hotel;
use Illuminate\Support\Facades\Log;

class QRTokenMiddleware
{
    public function __construct()
    {
    }

    /**
     * Handle the incoming request by validating QR token and setting tenant context.
     *
     * Extraction sources (in order of priority):
     * 1. Route parameter: {qrToken}
     * 2. Query parameter: ?qr_token=...
     * 3. Request body: JSON body with qr_token field
     * 4. Header: X-QR-Token header
     * 5. Request body: form-data qr_token
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $qrToken = $this->extractQRToken($request);

        if (!$qrToken) {
            Log::warning('QR token extraction failed', [
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'QR token is required for guest booking access',
            ], 401);
        }

        $resolution = QRResolutionService::resolveQRToken($qrToken);

        if (!$resolution['success']) {
            Log::warning('QR token validation failed', [
                'qr_token' => $qrToken,
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'reason' => $resolution['message'],
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => $resolution['message'] ?? 'Invalid QR token',
            ], 401);
        }

        $hotel = Hotel::first();

        if (!$hotel || !$hotel->isActive()) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Hotel is not available',
            ], 403);
        }

        $request->attributes->set('qr_resolution', $resolution);
        $request->attributes->set('qr_token', $qrToken);

        Log::info('QR token validated successfully', [
            'qr_token' => $qrToken,
            'context' => $resolution['context'],
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Extract QR token from multiple sources in priority order.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function extractQRToken(Request $request): ?string
    {
        $token = $request->route('qrToken');
        if ($token) {
            return $token;
        }

        $token = $request->query('qr_token');
        if ($token) {
            return $token;
        }

        $token = $request->json('qr_token');
        if ($token) {
            return $token;
        }

        $token = $request->header('X-QR-Token');
        if ($token) {
            return $token;
        }

        $token = $request->input('qr_token');
        if ($token) {
            return $token;
        }

        return null;
    }
}

