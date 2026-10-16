<?php

namespace App\Http\Requests;

use App\Models\Floor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('floor_id')) {
            $floorNumber = Floor::where('id', $this->floor_id)->value('floor_number');
            if ($floorNumber !== null) {
                $this->merge(['floor' => $floorNumber]);
            }
        } elseif ($this->filled('floor')) {
            $floorId = Floor::where('floor_number', $this->floor)->value('id');
            if ($floorId) {
                $this->merge(['floor_id' => $floorId]);
            }
        }

        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        if (!$this->filled('capacity') && $this->filled('room_type_id')) {
            $typeCapacity = \App\Models\RoomType::where('id', $this->room_type_id)->value('capacity');
            $this->merge(['capacity' => $typeCapacity ?: 2]);
        }
    }

    public function rules(): array
    {
        return [
            'room_number' => ['required', 'string', 'max:50', Rule::unique('rooms', 'room_number')],
            'floor_id' => ['required', Rule::exists('floors', 'id')],
            'room_type_id' => ['required', Rule::exists('room_types', 'id')],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'floor' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'status' => [
                'required',
                Rule::in([
                    'available',
                    'occupied',
                    'maintenance',
                    'cleaning',
                    'reserved',
                    'out_of_service',
                ]),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],
        ];
    }
}