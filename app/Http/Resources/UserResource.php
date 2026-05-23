<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'business_profile' => $this->whenLoaded('businessProfile', fn() => [
                'business_name'         => $this->businessProfile->business_name,
                'category'              => $this->businessProfile->category,
                'description'           => $this->businessProfile->description,
                'profile_photo'         => $this->businessProfile->profile_photo,
                'dark_mode'             => (bool) $this->businessProfile->dark_mode,
                'language'              => $this->businessProfile->language,
                'notification_enabled'  => (bool) $this->businessProfile->notification_enabled,
                'ai_tone'               => $this->businessProfile->ai_tone,
                'default_target_market' => $this->businessProfile->default_target_market,
                'default_platform'      => $this->businessProfile->default_platform,
                'phone'                 => $this->businessProfile->phone,
                'address'               => $this->businessProfile->address,
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
