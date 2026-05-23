<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAIRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',

            // Ubah dari 'required' jadi 'nullable' dengan default
            'type' => 'nullable|string|in:caption,marketplace,hashtag,promo,smart_reply,translate',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product ID wajib diisi.',
            'product_id.exists'   => 'Produk tidak ditemukan.',
            'type.in'             => 'Tipe konten tidak valid. Pilih: caption, marketplace, hashtag, promo, smart_reply, translate.',
        ];
    }
}
