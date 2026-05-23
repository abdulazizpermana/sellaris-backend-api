<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'product'          => $this->whenLoaded('product', fn() => [
                'id'           => $this->product->id,
                'product_name' => $this->product->product_name,
                'price'        => (float) $this->product->price,
            ]),
            'quantity'         => $this->quantity,
            'total_price'      => (float) $this->total_price,
            'total_formatted'  => 'Rp ' . number_format($this->total_price, 0, ',', '.'),
            'notes'            => $this->notes,
            'transaction_date' => $this->transaction_date->format('Y-m-d'),
        ];
    }
}
