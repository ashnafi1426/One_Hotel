<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationCollection;
use App\Http\Resources\ReservationResource;
use App\Http\Resources\RoomResource;
use App\Models\Reservation;
use App\Services\CheckInService;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ReservationController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
        protected CheckInService $checkInService
    ) {}

    /**
     * List reservations for the hotel.
     */
    public function index(Request $request): ReservationCollection|JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 10);
            $reservations = $this->reservationService->getReservations($request->all(), $perPage);
            return new ReservationCollection($reservations);
        } catch (\Throwable $e) {
            Log::error('[ReservationController] index failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'filters' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load reservations.',
                'error'   => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Store a newly created reservation.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        try {
            $reservation = $this->reservationService->createReservation(
                $request->validated(),
                null,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Reservation created and automatically confirmed.',
                'data' => new ReservationResource($reservation),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Show a reservation by ID or booking reference.
     */
    public function show(mixed $reservation): ReservationResource|JsonResponse
    {
        if ($reservation instanceof Reservation) {
            $model = $reservation;
        } else {
            $model = Reservation::where(function ($q) use ($reservation) {
                    $q->where('id', $reservation)
                      ->orWhere('booking_reference', $reservation);
                })
                ->first();

            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found.',
                ], 404);
            }
        }

        $model->loadMissing(['guest', 'room.roomType', 'creator', 'checkIn']);

        return new ReservationResource($model);
    }

    /**
     * Update an existing reservation.
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        try {
            $updated = $this->reservationService->updateReservation(
                $reservation,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Reservation updated.',
                'data' => new ReservationResource($updated),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Delete a reservation safely.
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->reservationService->deleteReservation($reservation);

        return response()->json([
            'success' => true,
            'message' => 'Reservation deleted successfully.',
        ]);
    }

    /**
     * Confirm a pending reservation.
     */
    public function confirm(Reservation $reservation): ReservationResource|JsonResponse
    {
        try {
            $confirmed = $this->reservationService->confirmReservation($reservation);

            return new ReservationResource($confirmed);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a reservation.
     */
    public function cancel(Reservation $reservation): ReservationResource|JsonResponse
    {
        try {
            $cancelled = $this->reservationService->cancelReservation($reservation);

            return new ReservationResource($cancelled);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check in a guest for a reservation.
     */
    public function checkIn(Reservation $reservation): ReservationResource|JsonResponse
    {
        try {
            $this->checkInService->checkIn($reservation);

            return new ReservationResource(
                $reservation->fresh(['guest', 'room.roomType', 'creator', 'checkIn'])
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Check out a guest for a reservation.
     */
    public function checkOut(Reservation $reservation): ReservationResource|JsonResponse
    {
        try {
            $this->checkInService->checkOut($reservation);

            return new ReservationResource(
                $reservation->fresh(['guest', 'room.roomType', 'creator', 'checkIn'])
            );
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Check room availability for given dates.
     */
    public function availability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'room_id' => 'nullable|uuid',
            'room_type_id' => 'nullable|uuid',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $result = $this->reservationService->checkAvailability($validated);

        if (!empty($validated['room_id'])) {
            return response()->json([
                'success' => $result['available'] ?? false,
                ...$result,
            ]);
        }

        return response()->json([
            'success' => true,
            'check_in_date' => $result['check_in_date'],
            'check_out_date' => $result['check_out_date'],
            'available_rooms_count' => $result['available_rooms_count'],
            'data' => RoomResource::collection($result['rooms']),
        ]);
    }
}
