<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BroadcastAuthController
 *
 * Handles WebSocket channel authorization for Laravel Echo / Reverb.
 * Supports both authenticated users and public guest order tracking.
 */
class BroadcastAuthController extends Controller
{
    /**
     * Authenticate WebSocket channel access.
     */
    public function authenticate(Request $request): JsonResponse
    {
        try {
            $channelName = $request->input('channel_name');
            $socketId = $request->input('socket_id');

            if (!$channelName || !$socketId) {
                return response()->json(['error' => 'Missing channel_name or socket_id'], 400);
            }

            Log::info('[BroadcastAuth] Channel auth request', [
                'channel' => $channelName,
                'socket_id' => $socketId,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip()
            ]);

            if (str_starts_with($channelName, 'private-orders.')) {
                return $this->authorizeOrderChannel($request, $channelName, $socketId);
            }

            if (str_starts_with($channelName, 'private-hotel.')) {
                return $this->authorizeHotelChannel($request, $channelName, $socketId);
            }

            if (str_starts_with($channelName, 'private-payments.')) {
                return $this->authorizePaymentsChannel($request, $channelName, $socketId);
            }

            Log::warning('[BroadcastAuth] Unknown channel type', ['channel' => $channelName]);
            return response()->json(['error' => 'Channel not found'], 404);

        } catch (\Exception $e) {
            Log::error('[BroadcastAuth] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Authorization failed'], 500);
        }
    }

    /**
     * Authorize access to order-specific channels: orders.{hotelId}.{orderId} or orders.{orderId}
     */
    protected function authorizeOrderChannel(Request $request, string $channelName, string $socketId): JsonResponse
    {
        $parts = explode('.', str_replace('private-', '', $channelName));

        // Format could be orders.{hotelId}.{orderId} or orders.{orderId}
        $orderId = end($parts);

        $order = Order::with(['room', 'table'])
            ->where('id', $orderId)
            ->orWhere('order_number', $orderId)
            ->first();

        if (!$order) {
            Log::warning('[BroadcastAuth] Order not found', [
                'channel' => $channelName,
                'order_id' => $orderId
            ]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        $qrToken = $request->input('qr_token') ?? $request->header('X-QR-Token');

        if ($qrToken) {
            $isValidToken = false;

            if ($order->room && $order->room->qr_token === $qrToken) {
                $isValidToken = true;
            } elseif ($order->table && $order->table->qr_token === $qrToken) {
                $isValidToken = true;
            } elseif (RestaurantTable::where('qr_token', $qrToken)->exists()) {
                $isValidToken = true;
            } elseif (Room::where('qr_token', $qrToken)->exists()) {
                $isValidToken = true;
            } elseif (Payment::where('order_id', $order->id)->where('metadata->qr_token', $qrToken)->exists()) {
                $isValidToken = true;
            }

            if (!$isValidToken) {
                $user = $request->user();
                if (!$user) {
                    Log::warning('[BroadcastAuth] Invalid QR token for guest WebSocket subscription', [
                        'channel' => $channelName,
                        'order_id' => $order->id,
                        'ip' => $request->ip()
                    ]);
                    return response()->json(['error' => 'Invalid QR token'], 403);
                }

                Log::info('[BroadcastAuth] Order channel authorized for authenticated staff user', [
                    'channel' => $channelName,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'user_role' => $user->role
                ]);
            } else {
                Log::info('[BroadcastAuth] Guest WebSocket channel authorized via QR token', [
                    'channel' => $channelName,
                    'order_id' => $order->id,
                ]);
            }
        } else {
            $user = $request->user();
            if (!$user) {
                Log::warning('[BroadcastAuth] No QR token and no authenticated user', [
                    'channel' => $channelName,
                    'order_id' => $order->id,
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            Log::info('[BroadcastAuth] Order channel authorized for authenticated user', [
                'channel' => $channelName,
                'order_id' => $order->id,
                'user_id' => $user->id
            ]);
        }

        $auth = $this->generateChannelAuth($channelName, $socketId);

        return response()->json([
            'auth' => $auth,
            'channel_data' => null
        ]);
    }

    /**
     * Authorize access to hotel-wide channels: hotel.{hotelId}.{scope}
     */
    protected function authorizeHotelChannel(Request $request, string $channelName, string $socketId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        Log::info('[BroadcastAuth] Hotel channel authorized', [
            'channel' => $channelName,
            'user_id' => $user->id,
        ]);

        $auth = $this->generateChannelAuth($channelName, $socketId);

        return response()->json([
            'auth' => $auth,
            'channel_data' => null
        ]);
    }

    /**
     * Authorize access to payment channels: payments.{hotelId}
     */
    protected function authorizePaymentsChannel(Request $request, string $channelName, string $socketId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        Log::info('[BroadcastAuth] Payments channel authorized', [
            'channel' => $channelName,
            'user_id' => $user->id,
        ]);

        $auth = $this->generateChannelAuth($channelName, $socketId);

        return response()->json([
            'auth' => $auth,
            'channel_data' => null
        ]);
    }

    /**
     * Generate channel authorization signature for Pusher protocol.
     */
    protected function generateChannelAuth(string $channelName, string $socketId): string
    {
        $appKey = config('broadcasting.connections.pusher.key');
        $appSecret = config('broadcasting.connections.pusher.secret');

        $stringToSign = $socketId . ':' . $channelName;
        $signature = hash_hmac('sha256', $stringToSign, $appSecret);

        return $appKey . ':' . $signature;
    }
}
