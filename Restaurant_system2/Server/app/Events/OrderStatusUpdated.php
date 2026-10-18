<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderStatusUpdated Event
 *
 * Broadcast when an order's status changes (pending → preparing → ready → served).
 *
 * Broadcasts to the kitchen.orders.update channel for real-time kitchen dashboard updates.
 *
 * Uses ShouldBroadcastNow to broadcast immediately (no queue worker needed).
 */
class OrderStatusUpdated implements ShouldBroadcastNow
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
            new Channel('kitchen.orders.update'),
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
            // Include full order data so frontend can display it
            'order' => [
                'id' => $this->orderId,
                'order_number' => $this->orderNumber,
                'status' => $this->status,
                'previous_status' => $this->previousStatus,
                'room_number' => $this->roomNumber,
                'table_number' => $this->tableNumber,
                'guest_name' => $guestName,
                'chef_id' => $this->chefId,
                'created_at' => $this->order->created_at,
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

