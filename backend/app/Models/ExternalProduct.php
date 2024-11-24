<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'external_id',
        'title',
        'price',
        'url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array', // Automatically handle JSON as an array
    ];

    /**
     * Relationship: External Product belongs to a Shop.
     */
    public function shop()
    {
        return $this->belongsTo(ExternalShop::class);
    }
}
