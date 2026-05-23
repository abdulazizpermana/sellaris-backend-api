<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'user_id',
        'product_name',
        'price',
        'stock',
        'image_url',
        'description',
        'target_market',
        'is_active'
    ];

    protected $casts = ['price' => 'decimal:2', 'is_active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function aiContent()
    {
        return $this->hasOne(AiContent::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
