<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'payment_type' => [
                'nullable',
                'string',
                Rule::in(['room_charge', 'cash', 'card', 'chapa', 'online']),
            ],
            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'items' => [
                'sometimes',
                'array',
                'min:1',
            ],
            'items.*.id' => [
                'nullable',
                'uuid',
                Rule::exists('order_items', 'id'),
            ],
            'items.*.menu_item_id' => [
                'required_with:items',
                'uuid',
                Rule::exists('menu_items', 'id'),
            ],
            'items.*.quantity' => [
                'required_with:items',
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