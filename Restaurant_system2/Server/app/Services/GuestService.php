<?php

namespace App\Services;

use App\Models\Guest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GuestService
{
    /**
     * Get paginated guests list for the single hotel.
     */
    public function getGuests(array $filters = [], int $perPage = 10, ?string $hotelId = null): LengthAwarePaginator
    {
        $query = Guest::query();

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('passport_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['nationality'])) {
            $query->where('nationality', $filters['nationality']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Find existing guest or create a new guest record.
     */
    public function findOrCreateGuest(array $data, ?string $hotelId = null): Guest
    {
        $email = !empty($data['email']) ? strtolower(trim($data['email'])) : null;
        $passport = !empty($data['passport_number']) ? trim($data['passport_number']) : null;
        $phone = !empty($data['phone']) ? trim($data['phone']) : null;

        $existingGuest = null;

        if ($email) {
            $existingGuest = Guest::where('email', $email)->first();
        }

        if (!$existingGuest && $passport) {
            $existingGuest = Guest::where('passport_number', $passport)->first();
        }

        if (!$existingGuest && $phone && $phone !== 'N/A') {
            $existingGuest = Guest::where('phone', $phone)->first();
        }

        if ($existingGuest) {
            $updateFields = array_filter([
                'first_name'      => $data['first_name'] ?? null,
                'last_name'       => $data['last_name'] ?? null,
                'phone'           => $phone && $phone !== 'N/A' ? $phone : null,
                'address'         => $data['address'] ?? null,
                'nationality'     => $data['nationality'] ?? null,
                'passport_number' => $passport,
                'date_of_birth'   => $data['date_of_birth'] ?? null,
                'preferences'     => $data['preferences'] ?? null,
            ], fn($v) => !is_null($v) && $v !== '');

            if (!empty($updateFields)) {
                $existingGuest->update($updateFields);
            }

            return $existingGuest;
        }

        return Guest::create([
            'first_name'      => $data['first_name'] ?? 'Guest',
            'last_name'       => $data['last_name'] ?? 'Booking',
            'email'           => $email,
            'phone'           => $phone ?? 'N/A',
            'address'         => $data['address'] ?? null,
            'nationality'     => $data['nationality'] ?? null,
            'passport_number' => $passport,
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'preferences'     => $data['preferences'] ?? null,
        ]);
    }

    /**
     * Get reservations for a guest.
     */
    public function getGuestReservations(Guest $guest, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $guest->reservations()->with(['room.roomType', 'creator']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }
}
