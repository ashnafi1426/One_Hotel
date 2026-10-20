<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\User;
use App\Events\OrderCreated;
use App\Services\QRResolutionService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuestOrderService
{
    /**
     * Process and place an order originating from a Guest QR scan (Room or Table).
     */
    public function placeOrder(array $data): array
    {
        $qrToken = $data['qr_token'];
        $resolution = QRResolutionService::resolveQRToken($qrToken);

        if (!$resolution['success']) {
            return [
                'status_code' => 404,
                'response' => [
                    'success' => false,
                    'error' => 'Invalid QR code',
                    'message' => $resolution['message'] ?? 'QR code token not found or expired.',
                ],
            ];
        }

        $context = $resolution['context'];
        $contextData = $resolution['data'];

        if ($context === 'room') {
            return $this->placeRoomOrder($data, $contextData);
        }

        if ($context === 'table') {
            return $this->placeTableOrder($data, $contextData);
        }

        return [
            'status_code' => 400,
            'response' => [
                'success' => false,
                'error' => 'Invalid context',
                'message' => 'Unrecognized ordering context.',
            ],
        ];
    }

    /**
     * Place a room-service order, enforcing checked-in reservation eligibility.
     */
    protected function placeRoomOrder(array $data, array $roomData): array
    {
        $room = Room::withoutGlobalScopes()->findOrFail($roomData['room_id']);
        $reservation = $room->getCurrentReservation();

        if (!$reservation || $reservation->status !== 'checked_in') {
            $statusText = $reservation ? $reservation->status : 'none';
            $errorMessage = match ($statusText) {
                'confirmed' => 'Your reservation is confirmed, but room-service ordering is only available after check-in at the front desk.',
                'checked_out' => 'This room has been checked out. Room-service ordering is no longer available.',
                'cancelled' => 'This reservation was cancelled. Room-service ordering is unavailable.',
                default => 'Room service is only available for checked-in guests. Please contact the front desk.',
            };

            return [
                'status_code' => 403,
                'response' => [
                    'success' => false,
                    'error' => 'Ordering not allowed',
                    'message' => $errorMessage,
                    'reservation_status' => $statusText,
                    'can_order' => false,
                ],
            ];
        }

        // 1. Calculate totals and validate menu items outside transaction (zero database locks held)
        list($total, $subtotal, $tax, $orderItems) = $this->calculateOrderTotal($data['items']);

        // 2. High-concurrency safe order creation with atomic retries
        $order = null;
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $order = DB::transaction(function () use ($data, $room, $reservation, $total, $subtotal, $tax, $orderItems) {
                    $createdOrder = Order::create([
                        'order_number' => Order::generateOrderNumber(),
                        'room_id' => $room->id,
                        'table_id' => null,
                        'guest_id' => $reservation->guest_id,
                        'reservation_id' => $reservation->id,
                        'order_type' => Order::TYPE_ROOM_SERVICE,
                        'order_time' => now(),
                        'total' => $total,
                        'subtotal' => $subtotal,
                        'taxable_amount' => $subtotal,
                        'tax' => $tax,
                        'status' => Order::STATUS_PENDING,
                        'source' => 'guest_qr',
                        'payment_type' => $data['payment_type'] ?? 'room_charge',
                        'notes' => $data['special_requests'] ?? null,
                    ]);

                    $this->createOrderItems($createdOrder->id, $orderItems);

                    return $createdOrder;
                });
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt < 5 && ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Deadlock') || str_contains($e->getMessage(), 'Lock wait timeout'))) {
                    usleep(random_int(10000, 40000));
                    continue;
                }
                throw $e;
            }
        }

        // 3. Post-transaction operations: order is already committed in DB, locks are released!
        $order->setRelation('room', $room);
        $order->setRelation('reservation', $reservation);
        if ($reservation->relationLoaded('guest') && $reservation->guest) {
            $order->setRelation('guest', $reservation->guest);
        }
        $order->loadMissing([
            'orderItems.menuItem',
        ]);

        $dispatchPostTasks = function () use ($order, $room) {
            try {
                OrderCreated::dispatch($order);
            } catch (\Throwable $e) {
                Log::warning("Failed to broadcast room order creation: {$e->getMessage()}");
            }

            $this->notifyChefs($order, "Room order #{$order->order_number} (Room {$room->room_number}) has been placed.");
        };

        if (function_exists('Illuminate\Support\defer')) {
            \Illuminate\Support\defer($dispatchPostTasks);
        } elseif (function_exists('app') && !app()->runningInConsole()) {
            app()->terminating($dispatchPostTasks);
        } else {
            $dispatchPostTasks();
        }

        return [
            'status_code' => 201,
            'response' => [
                'success' => true,
                'message' => 'Room service order placed successfully',
                'data' => [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'room_number' => $room->room_number,
                    'total' => (float) $order->total,
                    'status' => $order->status,
                    'items' => $orderItems,
                    'created_at' => $order->created_at->toIso8601String(),
                ],
            ],
        ];
    }

    /**
     * Place a walk-in table order (does NOT require hotel reservation check-in).
     */
    protected function placeTableOrder(array $data, array $tableData): array
    {
        $table = RestaurantTable::withoutGlobalScopes()->findOrFail($tableData['table_id']);

        // 1. Calculate totals outside transaction
        list($total, $subtotal, $tax, $orderItems) = $this->calculateOrderTotal($data['items']);

        // 2. High-concurrency safe order creation with atomic retries
        $order = null;
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                $order = DB::transaction(function () use ($data, $table, $total, $subtotal, $tax, $orderItems) {
                    $createdOrder = Order::create([
                        'order_number' => Order::generateOrderNumber(),
                        'room_id' => null,
                        'table_id' => $table->id,
                        'guest_id' => null,
                        'reservation_id' => null,
                        'order_type' => Order::TYPE_DINE_IN,
                        'order_time' => now(),
                        'total' => $total,
                        'subtotal' => $subtotal,
                        'taxable_amount' => $subtotal,
                        'tax' => $tax,
                        'status' => Order::STATUS_PENDING,
                        'source' => 'guest_qr',
                        'payment_type' => $data['payment_type'] ?? 'cash',
                        'notes' => $data['special_requests'] ?? null,
                    ]);

                    $this->createOrderItems($createdOrder->id, $orderItems);

                    if ($table->status !== RestaurantTable::STATUS_OCCUPIED) {
                        $table->update(['status' => RestaurantTable::STATUS_OCCUPIED]);
                    }

                    return $createdOrder;
                });
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempt < 5 && ($e->getCode() == 23000 || str_contains($e->getMessage(), 'Deadlock') || str_contains($e->getMessage(), 'Lock wait timeout'))) {
                    usleep(random_int(10000, 40000));
                    continue;
                }
                throw $e;
            }
        }

        // 3. Post-transaction operations: order is already committed in DB, locks are released!
        $order->setRelation('table', $table);
        $order->loadMissing([
            'orderItems.menuItem',
        ]);

        $dispatchPostTasks = function () use ($order, $table) {
            try {
                OrderCreated::dispatch($order);
            } catch (\Throwable $e) {
                Log::warning("Failed to broadcast table order creation: {$e->getMessage()}");
            }

            $this->notifyChefs($order, "Table order #{$order->order_number} (Table {$table->table_number}) has been placed.");
        };

        if (function_exists('Illuminate\Support\defer')) {
            \Illuminate\Support\defer($dispatchPostTasks);
        } elseif (function_exists('app') && !app()->runningInConsole()) {
            app()->terminating($dispatchPostTasks);
        } else {
            $dispatchPostTasks();
        }

        return [
            'status_code' => 201,
            'response' => [
                'success' => true,
                'message' => 'Table order placed successfully',
                'data' => [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type,
                    'table_number' => $table->table_number,
                    'total' => (float) $order->total,
                    'status' => $order->status,
                    'items' => $orderItems,
                    'created_at' => $order->created_at->toIso8601String(),
                ],
            ],
        ];
    }

    /**
     * Compute line totals, tax calculations, and validate menu items.
     * Batches item lookups to avoid N+1 queries.
     */
    public function calculateOrderTotal(array $items, ?string $hotelId = null): array
    {
        $grandTotal = 0;
        $totalSubtotal = 0;
        $totalTax = 0;
        $orderItems = [];

        $menuItemIds = array_unique(array_filter(array_column($items, 'menu_item_id')));
        $menuItems = MenuItem::withoutGlobalScopes()
            ->with('taxRate')
            ->whereIn('id', $menuItemIds)
            ->get()
            ->keyBy(fn ($item) => (string) $item->id);

        foreach ($items as $item) {
            $itemId = (string) $item['menu_item_id'];
            $menuItem = $menuItems->get($itemId);

            if (!$menuItem) {
                $menuItem = MenuItem::withoutGlobalScopes()->with('taxRate')->findOrFail($itemId);
            }

            if (!$menuItem->is_available) {
                throw new \InvalidArgumentException("Menu item '{$menuItem->name}' is currently unavailable.");
            }

            $price = (float) $menuItem->price;
            $quantity = max(1, (int) $item['quantity']);
            $taxRate = $menuItem->taxRate;
            $rate = ($taxRate && $taxRate->is_active) ? (float) $taxRate->rate : 0.0;
            $taxIncluded = (bool) ($menuItem->tax_included ?? false);

            if ($rate > 0) {
                if ($taxIncluded) {
                    $baseUnit = round($price / (1 + ($rate / 100)), 4);
                    $subtotal = round($baseUnit * $quantity, 2);
                    $lineTotal = round($price * $quantity, 2);
                    $taxAmount = round($lineTotal - $subtotal, 2);
                } else {
                    $subtotal = round($price * $quantity, 2);
                    $taxAmount = round($subtotal * ($rate / 100), 2);
                    $lineTotal = round($subtotal + $taxAmount, 2);
                }
            } else {
                $subtotal = round($price * $quantity, 2);
                $taxAmount = 0.0;
                $lineTotal = $subtotal;
            }

            $totalSubtotal += $subtotal;
            $totalTax += $taxAmount;
            $grandTotal += $lineTotal;

            $orderItems[] = [
                'menu_item_id' => $menuItem->id,
                'item_name' => $menuItem->name,
                'quantity' => $quantity,
                'item_price_at_order' => $price,
                'tax_rate_id' => $rate > 0 ? $menuItem->tax_rate_id : null,
                'tax_rate' => $rate,
                'tax_amount' => $taxAmount,
                'subtotal' => $subtotal,
                'total' => $lineTotal,
                'line_total' => $lineTotal,
                'notes' => $item['notes'] ?? null,
            ];
        }

        return [
            round($grandTotal, 2),
            round($totalSubtotal, 2),
            round($totalTax, 2),
            $orderItems,
        ];
    }

    /**
     * Persist order items with complete historical snapshots in a single bulk insert.
     */
    protected function createOrderItems(string $orderId, array $items): void
    {
        $now = now();
        $records = [];
        foreach ($items as $item) {
            $records[] = [
                'id' => (string) Str::uuid(),
                'order_id' => $orderId,
                'menu_item_id' => $item['menu_item_id'],
                'item_name' => $item['item_name'] ?? null,
                'quantity' => $item['quantity'],
                'item_price_at_order' => $item['item_price_at_order'],
                'tax_rate_id' => $item['tax_rate_id'] ?? null,
                'tax_rate' => $item['tax_rate'] ?? 0,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'subtotal' => $item['subtotal'] ?? $item['line_total'],
                'total' => $item['total'] ?? $item['line_total'],
                'line_total' => $item['line_total'],
                'notes' => $item['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($records)) {
            OrderItem::insert($records);
        }
    }

    /**
     * Send order notifications to on-duty chefs using a single bulk insert.
     */
    protected function notifyChefs(Order $order, string $message): void
    {
        try {
            $chefIds = Cache::remember('active_chef_ids', 600, function () {
                return User::where('role', 'chef')->pluck('id')->toArray();
            });

            if (empty($chefIds)) {
                return;
            }

            $now = now();
            $notifications = [];
            foreach ($chefIds as $chefId) {
                $notifications[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $chefId,
                    'type' => 'order_created',
                    'title' => 'New Order Received',
                    'message' => $message,
                    'read' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Notification::insert($notifications);
        } catch (\Throwable $e) {
            Log::warning('Failed to send chef notification: ' . $e->getMessage());
        }
    }
}
