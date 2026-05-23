<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                    => 'sometimes|required|string|max:255',
            'business_name'           => 'sometimes|required|string|max:255',
            'business_category'       => 'sometimes|nullable|string|max:100',
            'business_description'    => 'sometimes|nullable|string',
            'profile_photo'           => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'dark_mode'               => 'sometimes|boolean',
            'language'                => 'sometimes|string|max:10',
            'notification_enabled'    => 'sometimes|boolean',
            'ai_tone'                 => 'sometimes|nullable|string|max:100',
            'default_target_market'   => 'sometimes|nullable|string|max:255',
            'default_platform'        => 'sometimes|nullable|string|max:255',
        ];
    }
}
