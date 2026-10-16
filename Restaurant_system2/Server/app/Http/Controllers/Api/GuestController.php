<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestRequest;
use App\Http\Resources\GuestCollection;
use App\Http\Resources\GuestResource;
use App\Http\Resources\ReservationCollection;
use App\Models\Guest;
use App\Services\GuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function __construct(
        protected GuestService $guestService
    ) {}

    /**
     * Display a listing of guests.
     */
    public function index(Request $request): GuestCollection
    {
        $perPage = $request->integer('per_page', 10);
        $guests = $this->guestService->getGuests($request->all(), $perPage);

        return new GuestCollection($guests);
    }

    /**
     * Store or update guest profile.
     */
    public function store(GuestRequest $request): JsonResponse
    {
        $guest = $this->guestService->findOrCreateGuest($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Guest record saved successfully.',
            'data'    => new GuestResource($guest),
        ], 201);
    }

    /**
     * Display the specified guest.
     */
    public function show(Guest $guest): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new GuestResource($guest),
        ]);
    }

    /**
     * Get reservations for a guest.
     */
    public function reservations(Guest $guest, Request $request): ReservationCollection
    {
        $perPage = $request->integer('per_page', 10);
        $reservations = $this->guestService->getGuestReservations($guest, $request->all(), $perPage);

        return new ReservationCollection($reservations);
    }

    /**
     * Update the specified guest.
     */
    public function update(GuestRequest $request, Guest $guest): JsonResponse
    {
        $guest->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Guest updated successfully.',
            'data'    => new GuestResource($guest->fresh()),
        ]);
    }

    /**
     * Remove the specified guest.
     */
    public function destroy(Guest $guest): JsonResponse
    {
        $guest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guest deleted successfully.',
        ]);
    }
}
