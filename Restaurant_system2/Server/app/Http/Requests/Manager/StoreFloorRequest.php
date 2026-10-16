<?php

namespace App\Http\Requests\Manager;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFloorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'floor_number' => [
                'required',
                'integer',
                'min:-10',
                'max:200',
                'unique:floors,floor_number',
            ],
            'name' => [
                'required',
                'string',
                'min:1',
                'max:100',
                'unique:floors,name',
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'total_rooms' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'floor_number.required' => 'Floor number is required.',
            'floor_number.unique' => 'A floor with this number already exists in your hotel.',
            'name.required' => 'Floor name is required.',
            'name.unique' => 'A floor with this name already exists in your hotel.',
        ];
    }
}

