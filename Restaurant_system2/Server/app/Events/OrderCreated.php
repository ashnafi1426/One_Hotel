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
            new Channel('kitchen.orders.update'),
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
            // Send complete order object with all relations (matching OrderStatusUpdated format)
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'order_type' => $this->order->order_type,
                'status' => $this->order->status,
                'previous_status' => null,
                'order_time' => $this->order->created_at?->toISOString(),
                'guest' => $this->order->guest ? [
                    'id' => $this->order->guest->id,
                    'first_name' => $this->order->guest->first_name ?? '',
                    'last_name' => $this->order->guest->last_name ?? '',
                    'full_name' => $guestName ?: 'Guest',
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
                'updated_at' => $this->order->updated_at?->toISOString(),
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
