<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'category',
        'description',
        'phone',
        'address',
        'profile_photo',
        'dark_mode',
        'language',
        'notification_enabled',
        'ai_tone',
        'default_target_market',
        'default_platform',
    ];

    protected $casts = [
        'dark_mode'            => 'boolean',
        'notification_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
