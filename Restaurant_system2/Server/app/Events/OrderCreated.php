<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Order $order,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders.update.' . $this->order->id),
            new PrivateChannel('private-kitchen.orders.update'),
            new Channel('waiter.orders.update'),
            new Channel('manager.orders.update'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $guestName = trim(
            ($this->order->guest?->first_name ?? '') . ' ' . ($this->order->guest?->last_name ?? '')
        ) ?: null;

        return [
            'id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'created_at' => $this->order->created_at,
            'guest_name' => $guestName,
            'room_number' => $this->order->room?->room_number,
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
                'order_type' => $this->order->order_type,
                'total' => $this->order->total,
                'created_at' => $this->order->created_at,
                'updated_at' => $this->order->updated_at,
                'guest_name' => $guestName,
                'room_number' => $this->order->room?->room_number,
            ],
        ];
    }

    /**
     * Get the name of the event.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }
}
