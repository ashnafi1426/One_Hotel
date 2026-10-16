<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roomRule = Rule::exists('rooms', 'id')->where('is_active', true);
        $guestRule = Rule::exists('guests', 'id');

        return [
            'guest_id' => [
                'nullable',
                'uuid',
                $guestRule,
            ],
            'first_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'last_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'room_id' => [
                'required',
                'uuid',
                $roomRule,
            ],
            'check_in_date' => [
                'required',
                'date',
            ],
            'check_out_date' => [
                'required',
                'date',
                'after:check_in_date',
            ],
            'number_of_guests' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'special_requests' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}