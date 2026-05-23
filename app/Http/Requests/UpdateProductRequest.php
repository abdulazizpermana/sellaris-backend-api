<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name'  => 'sometimes|required|string|max:255',
            'price'         => 'sometimes|required|numeric|min:0',
            'stock'         => 'sometimes|required|integer|min:0',
            'description'   => 'sometimes|nullable|string',
            'target_market' => 'sometimes|nullable|string|max:255',
            'image'         => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active'     => 'sometimes|boolean',
        ];
    }
}
