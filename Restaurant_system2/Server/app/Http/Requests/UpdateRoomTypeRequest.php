<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomTypeParam = $this->route('roomType');
        $roomTypeId = is_object($roomTypeParam) ? $roomTypeParam->id : $roomTypeParam;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('room_types', 'name')->ignore($roomTypeId, 'id'),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'base_price_per_night' => [
                'required',
                'numeric',
                'min:0',
            ],
            'capacity' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'amenities' => [
                'nullable',
                'array',
            ],
            'amenities.*' => [
                'string',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}