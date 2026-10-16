<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRestaurantSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sectionId = $this->route('id') ?: $this->route('section');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('restaurant_sections', 'name')->ignore($sectionId),
            ],
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ];
    }
}