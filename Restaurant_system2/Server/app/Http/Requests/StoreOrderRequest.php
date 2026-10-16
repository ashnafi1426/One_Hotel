<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'order_type' => [
                'nullable',
                'string',
                Rule::in([
                    Order::TYPE_ROOM_SERVICE,
                    Order::TYPE_DINE_IN,
                    Order::TYPE_WALK_IN,
                ]),
            ],
            'room_id' => [
                Rule::requiredIf(fn() => $this->input('order_type') === Order::TYPE_ROOM_SERVICE),
                'nullable',
                'uuid',
                Rule::exists('rooms', 'id'),
            ],
            'reservation_id' => [
                'nullable',
                'uuid',
                Rule::exists('reservations', 'id'),
            ],
            'guest_id' => [
                'nullable',
                'uuid',
                Rule::exists('guests', 'id'),
            ],
            'table_id' => [
                Rule::requiredIf(fn() => $this->input('order_type') === Order::TYPE_DINE_IN),
                'nullable',
                'uuid',
                Rule::exists('restaurant_tables', 'id'),
            ],
            'payment_type' => [
                'nullable',
                'string',
                Rule::in(['room_charge', 'cash', 'card', 'chapa', 'online']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.menu_item_id' => [
                'required',
                'uuid',
                Rule::exists('menu_items', 'id'),
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'items.*.notes' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}