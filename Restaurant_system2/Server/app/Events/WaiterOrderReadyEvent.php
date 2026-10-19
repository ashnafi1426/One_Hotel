<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaiterOrderReadyEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public int $waiterId;

    public function __construct(Order $order, int $waiterId)
    {
        $this->order = $order;
        $this->waiterId = $waiterId;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("waiter.{$this->waiterId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.ready';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status,
            'room_number' => $this->order->room?->room_number,
            'table_number' => $this->order->table?->table_number,
            'guest_name' => $this->order->guest?->name,
            'total' => $this->order->total,
            'created_at' => $this->order->created_at?->toIso8601String(),
            'message' => "Order #{$this->order->order_number} is ready for pickup",
        ];
    }
}
