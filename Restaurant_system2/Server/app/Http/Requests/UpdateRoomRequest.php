<?php

namespace App\Http\Requests;

use App\Models\Floor;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
        } elseif ($this->has('floor') && ($this->floor === '' || $this->floor === null)) {
            $this->merge(['floor' => null, 'floor_id' => null]);
        }

        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            ]);
        }
    }

    public function rules(): array
    {
        $roomParam = $this->route('room');
        $roomId = is_object($roomParam) ? $roomParam->id : $roomParam;

        return [
            'room_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('rooms', 'room_number')->ignore($roomId, 'id'),
            ],
            'room_type_id' => ['required', Rule::exists('room_types', 'id')],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'floor' => ['nullable', 'integer'],
            'floor_id' => ['nullable', Rule::exists('floors', 'id')],
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