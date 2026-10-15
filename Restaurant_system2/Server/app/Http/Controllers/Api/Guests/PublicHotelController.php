<?php

namespace App\Http\Controllers\Api\Guests;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;

class PublicHotelController extends Controller
{
    public function index(): JsonResponse
    {
        $hotel = Hotel::first();

        return response()->json([
            'success' => true,
            'data' => $hotel ? [$hotel] : [],
            'hotel' => $hotel,
        ]);
    }

    public function show(?string $slug = null): JsonResponse
    {
        $hotel = Hotel::first();

        if (!$hotel) {
            return response()->json([
                'success' => false,
                'message' => 'Hotel not found or unavailable',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $hotel,
        ]);
    }
}
