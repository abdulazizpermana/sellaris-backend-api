<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'product_name'  => $this->product_name,
            'price'         => (float) $this->price,
            'price_formatted' => 'Rp ' . number_format($this->price, 0, ',', '.'),
            'stock'         => $this->stock,
            'image_url'     => $this->image_url
                ? url('/api/product-images/' . basename($this->image_url))
                : null,
            'description'   => $this->description,
            'target_market' => $this->target_market,
            'is_active'     => $this->is_active,
            'ai_content'    => new AiContentResource($this->whenLoaded('aiContent')),
            'created_at'    => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
