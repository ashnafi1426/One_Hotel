<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'category' => [
                'required',
                'string',
                Rule::exists('categories', 'slug')->where('is_active', true),
            ],
            'category_id' => [
                'nullable',
                'uuid',
                Rule::exists('categories', 'id'),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'image_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'is_available' => [
                'sometimes',
                'boolean',
            ],
            'tax_rate_id' => [
                'nullable',
                'uuid',
                Rule::exists('tax_rates', 'id')->where('is_active', true),
            ],
            'tax_included' => [
                'sometimes',
                'boolean',
            ],
            'dietary_tags' => [
                'sometimes',
                'array',
            ],
            'dietary_tags.*' => [
                'string',
                'max:50',
            ],
        ];
    }
}