<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AiContentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'content'      => $this->generated_content ?? $this->instagram_caption,
            'generated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
