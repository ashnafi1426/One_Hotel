<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderStatusUpdated Event
 *
 * Broadcast when an order's status changes (pending → preparing → ready → served).
 *
 * Broadcasts to the kitchen.orders.update channel for real-time kitchen dashboard updates.
 *
 * Uses ShouldBroadcast to queue broadcasts asynchronously in the background.
 */
class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $orderId;
    public string $hotelId;
    public string $status;
    public ?string $previousStatus;
    public string $orderNumber;
    public ?string $roomNumber;
    public ?string $tableNumber;
    public ?string $chefId;
    public ?string $message;
    public ?int $estimatedCompletionMinutes;
    public Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?string $previousStatus = null)
    {
        $this->order = $order;
        $this->orderId = $order->id;
        $this->hotelId = (string) ($order->hotel_id ?? '41b094f8-7ed2-4af0-bd16-6f0533e34544');
        $this->status = $order->status;
        $this->previousStatus = $previousStatus;
        $this->orderNumber = $order->order_number;
        $this->roomNumber = $order->room?->room_number;
        $this->tableNumber = $order->table?->table_number;
        $this->chefId = $order->chef_id;
        $this->message = $this->generateStatusMessage($order->status);
        $this->estimatedCompletionMinutes = $this->estimateCompletionTime($order);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders.update.' . $this->orderId),
            new Channel('kitchen.orders.update'),
            new Channel('waiter.orders.update'),
            new Channel('manager.orders.update'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'OrderStatusUpdated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $guestName = trim(
            ($this->order->guest?->first_name ?? '') . ' ' . ($this->order->guest?->last_name ?? '')
        ) ?: null;

        return [
            // Use 'id' for consistency with OrderCreated event
            'id' => $this->orderId,
            'order_id' => $this->orderId,
            'hotel_id' => $this->hotelId,
            'status' => $this->status,
            'previous_status' => $this->previousStatus,
            'order_number' => $this->orderNumber,
            'room_number' => $this->roomNumber,
            'table_number' => $this->tableNumber,
            'chef_id' => $this->chefId,
            'message' => $this->message,
            'estimated_completion_minutes' => $this->estimatedCompletionMinutes,
            'updated_at' => now()->toISOString(),
            // Include full order data with all relations for frontend display
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'order_type' => $this->order->order_type,
                'status' => $this->status,
                'previous_status' => $this->previousStatus,
                'order_time' => $this->order->created_at?->toISOString(),
                'guest' => $this->order->guest ? [
                    'id' => $this->order->guest->id,
                    'first_name' => $this->order->guest->first_name ?? '',
                    'last_name' => $this->order->guest->last_name ?? '',
                    'full_name' => trim(($this->order->guest->first_name ?? '') . ' ' . ($this->order->guest->last_name ?? '')) ?: 'Guest',
                ] : null,
                'room' => $this->order->room ? [
                    'id' => $this->order->room->id,
                    'room_number' => $this->order->room->room_number,
                ] : null,
                'table' => $this->order->table ? [
                    'id' => $this->order->table->id,
                    'table_number' => $this->order->table->table_number,
                    'table_name' => $this->order->table->table_name ?? null,
                ] : null,
                'reservation' => $this->order->reservation ? [
                    'id' => $this->order->reservation->id,
                    'booking_reference' => $this->order->reservation->booking_reference,
                ] : null,
                'items' => $this->order->orderItems->map(function ($item) {
                    $menuItem = $item->menuItem ?? $item->menu_item;
                    $itemName = $menuItem?->name ?? $item->item_name ?? 'Dish';
                    $imageUrl = $menuItem?->image_url ?? ($menuItem?->image ? asset('storage/' . ltrim($menuItem->image, '/')) : null);
                    return [
                        'id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'name' => $itemName,
                        'item_name' => $itemName,
                        'category' => $menuItem?->category ?? 'Other',
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) ($item->item_price_at_order ?? $item->unit_price ?? $menuItem?->price ?? 0),
                        'line_total' => (float) ($item->line_total ?? $item->total ?? 0),
                        'notes' => $item->notes,
                        'note' => $item->notes,
                        'image' => $imageUrl,
                        'image_url' => $imageUrl,
                    ];
                })->toArray(),
                'subtotal' => (float) ($this->order->subtotal ?? 0),
                'tax' => (float) ($this->order->tax ?? 0),
                'discount' => (float) ($this->order->discount ?? 0),
                'total' => (float) ($this->order->total ?? 0),
                'notes' => $this->order->notes,
                'created_at' => $this->order->created_at?->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Generate human-readable status message.
     */
    protected function generateStatusMessage(string $status): string
    {
        return match($status) {
            'pending' => 'Your order has been received and is waiting to be prepared.',
            'preparing' => 'Our chef is preparing your delicious meal.',
            'ready' => 'Your order is ready! Please proceed to pick it up or wait for delivery.',
            'served' => 'Your order has been completed. Enjoy your meal!',
            'cancelled' => 'This order has been cancelled.',
            default => 'Order status updated.',
        };
    }

    /**
     * Estimate completion time based on order status.
     */
    protected function estimateCompletionTime(Order $order): ?int
    {
        if ($order->status === 'pending') {

            $itemCount = $order->orderItems()->count();
            return min(max($itemCount * 5, 15), 45);
        }

        if ($order->status === 'preparing') {

            return 20;
        }

        if ($order->status === 'ready') {
            return 0;
        }

        return null;
    }
}

