<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiContent extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'type',
        'generated_content',
        'instagram_caption',
        'marketplace_description',
        'hashtags',
        'english_translation',
        'promo_text',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
