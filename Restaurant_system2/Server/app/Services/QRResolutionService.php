<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QRResolutionService
{
    public static function resolveQRToken(string $qrToken, bool $forceRefresh = false): array
    {
        try {
            $rawToken = trim($qrToken);
            if (empty($rawToken)) {
                return [
                    'success' => false,
                    'context' => null,
                    'data' => null,
                    'message' => 'QR token cannot be empty',
                ];
            }

            $upperToken = strtoupper($rawToken);
            $cacheKey = "qr_token_res:" . md5($upperToken);

            if ($forceRefresh) {
                Cache::store('file')->forget($cacheKey);
            }

            return Cache::store('file')->remember($cacheKey, 300, function () use ($rawToken, $upperToken, $qrToken) {
                $lowerToken = strtolower($rawToken);

                // 1. Fast Table check (table QR tokens are fast 8-char codes)
                $table = RestaurantTable::withoutGlobalScopes()
                    ->where('qr_token', $rawToken)
                    ->orWhere('qr_token', $upperToken)
                    ->orWhere('qr_token', $lowerToken)
                    ->first();

                if (!$table && (strlen($rawToken) <= 10 || str_starts_with(strtolower($rawToken), 'tab'))) {
                    $table = RestaurantTable::withoutGlobalScopes()
                        ->where('table_number', $rawToken)
                        ->orWhere('table_number', $upperToken)
                        ->orWhere('id', $rawToken)
                        ->first();
                }

                $hotelInfo = Cache::store('file')->remember('default_hotel_info', 3600, function () {
                    $hotel = \App\Models\Hotel::first();
                    return [
                        'id' => $hotel?->id,
                        'name' => $hotel?->name ?? 'Sheraten Hotel',
                    ];
                });

                if ($table && $table->is_active !== false) {
                    return [
                        'success' => true,
                        'context' => 'table',
                        'data' => [
                            'hotel_id' => $hotelInfo['id'],
                            'hotel_name' => $hotelInfo['name'],
                            'table_id' => $table->id,
                            'table_number' => $table->table_number,
                            'table_name' => $table->table_name ?? ('Table ' . $table->table_number),
                            'capacity' => $table->capacity,
                            'location' => $table->location,
                            'section_id' => $table->section_id,
                            'section' => $table->section_name,
                            'status' => $table->status,
                            'assigned_waiter' => null,
                            'is_checked_in' => true,
                            'can_order' => true,
                            'reservation_status' => 'not_applicable',
                            'eligibility_message' => null,
                        ],
                        'message' => 'QR code belongs to a restaurant table',
                    ];
                }

                // 2. Room check
                $room = Room::withoutGlobalScopes()->where(function ($q) use ($rawToken, $upperToken, $lowerToken) {
                    $q->where('qr_token', $rawToken)
                      ->orWhere('qr_token', $upperToken)
                      ->orWhere('qr_token', $lowerToken)
                      ->orWhere('id', $rawToken);
                })->first();

                if ($room && $room->is_active !== false) {
                    $currentReservation = $room->getCurrentReservation();
                    $reservationStatus = $currentReservation?->status ?? 'none';
                    $canOrder = ($reservationStatus === 'checked_in');

                    $eligibilityMessage = match($reservationStatus) {
                        'checked_in' => 'Guest is checked in and eligible for room service.',
                        'confirmed' => 'Your reservation is confirmed, but room-service ordering is only available after check-in at the front desk.',
                        'checked_out' => 'This room has been checked out. Room-service ordering is no longer available.',
                        'cancelled' => 'This reservation was cancelled. Room-service ordering is unavailable.',
                        default => 'No active checked-in reservation found for this room. Room-service ordering is only available for checked-in guests.',
                    };

                    $guestInfo = null;
                    if ($currentReservation && $currentReservation->guest) {
                        $guest = $currentReservation->guest;
                        $guestInfo = [
                            'guest_id' => $guest->id,
                            'guest_name' => trim(($guest->first_name ?? '') . ' ' . ($guest->last_name ?? '')),
                            'guest_email' => $guest->email,
                            'guest_phone' => $guest->phone,
                            'reservation_id' => $currentReservation->id,
                            'reservation_status' => $reservationStatus,
                            'check_in_date' => $currentReservation->check_in_date?->format('Y-m-d'),
                            'expected_checkout' => $currentReservation->check_out_date?->format('Y-m-d'),
                        ];
                    }

                    return [
                        'success' => true,
                        'context' => 'room',
                        'data' => [
                            'hotel_id' => $hotelInfo['id'],
                            'hotel_name' => $hotelInfo['name'],
                            'room_id' => $room->id,
                            'room_number' => $room->room_number,
                            'floor' => $room->floor,
                            'floor_id' => $room->floor_id,
                            'room_type' => $room->roomType ? $room->roomType->name : null,
                            'status' => $room->status,
                            'guest' => $guestInfo,
                            'reservation_id' => $currentReservation?->id,
                            'reservation_status' => $reservationStatus,
                            'is_checked_in' => $canOrder,
                            'can_order' => $canOrder,
                            'eligibility_message' => $eligibilityMessage,
                        ],
                        'message' => $canOrder
                            ? 'QR code belongs to a hotel room'
                            : $eligibilityMessage,
                    ];
                }

            Log::warning('QR Token not found', ['token' => $rawToken]);
            return [
                'success' => false,
                'context' => null,
                'data' => null,
                'message' => 'QR code not found or has been deactivated',
            ];
            });

        } catch (\Exception $e) {
            Log::error('QR Token Resolution Error', [
                'token' => $qrToken,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'context' => null,
                'data' => null,
                'message' => 'Failed to resolve QR code',
            ];
        }
    }

    public static function validateOrderData(array $orderData, string $context): array
    {
        $errors = [];

        if ($context === 'room') {
            if (empty($orderData['room_id'])) {
                $errors[] = 'room_id is required for room service orders';
            } else {
                $room = Room::withoutGlobalScopes()->find($orderData['room_id']);
                if (!$room) {
                    $errors[] = 'Room not found';
                } else {
                    $reservation = $room->getCurrentReservation();
                    if (!$reservation || $reservation->status !== 'checked_in') {
                        $currentStatus = $reservation ? $reservation->status : 'none';
                        $errors[] = match($currentStatus) {
                            'confirmed' => 'Your reservation is confirmed, but room-service ordering is only available after check-in at the front desk.',
                            'checked_out' => 'This room has been checked out. Room-service ordering is no longer available.',
                            'cancelled' => 'This reservation was cancelled. Room-service ordering is unavailable.',
                            default => 'Room service ordering is only allowed for checked-in guests.',
                        };
                    }
                }
            }

        } elseif ($context === 'table') {
            if (empty($orderData['table_id'])) {
                $errors[] = 'table_id is required for walk-in orders';
            }

            if (!empty($orderData['room_id']) || !empty($orderData['guest_id']) || !empty($orderData['reservation_id'])) {
                $errors[] = 'Walk-in orders should not have room, guest, or reservation associations';
            }

        } else {
            $errors[] = 'Invalid order context';
        }

        if (empty($orderData['items']) || !is_array($orderData['items']) || count($orderData['items']) === 0) {
            $errors[] = 'Order must contain at least one item';
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    public static function getOrderTypeFromContext(string $context): string
    {
        return $context === 'room' ? 'room_service' : 'walk_in';
    }

    public static function isTableAvailable(string $qrToken): bool
    {
        $table = RestaurantTable::where('qr_token', $qrToken)
                                ->where('is_active', true)
                                ->where('status', RestaurantTable::STATUS_AVAILABLE)
                                ->first();

        return $table !== null;
    }

    public static function isRoomAvailable(string $qrToken): bool
    {
        $room = Room::where('qr_token', $qrToken)
                    ->where('is_active', true)
                    ->whereIn('status', ['occupied', 'available'])
                    ->first();

        return $room !== null;
    }
}

