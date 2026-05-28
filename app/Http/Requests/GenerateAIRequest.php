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
            'product_id' => 'required|integer|exists:products,id',
            'type'       => 'nullable|string|in:caption,marketplace,hashtag,promo,smart_reply,translate',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product ID wajib diisi.',
            'product_id.integer'  => 'Product ID harus berupa angka.',
            'product_id.exists'   => 'Produk tidak ditemukan.',
            'type.in'             => 'Tipe tidak valid. Pilih: caption, marketplace, hashtag, promo, smart_reply, translate.',
        ];
    }

    // ← Tambahkan prepareForValidation untuk handle tipe data
    protected function prepareForValidation(): void
    {
        // Pastikan product_id dikonversi ke integer
        if ($this->has('product_id')) {
            $this->merge([
                'product_id' => (int) $this->product_id,
            ]);
        }
    }
}
