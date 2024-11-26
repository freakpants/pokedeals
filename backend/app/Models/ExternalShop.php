<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalShop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'image'
    ];

    // disable timestamps
    public $timestamps = false;

    /**
     * Define the relationship with external products.
     */
    public function products()
    {
        return $this->hasMany(ExternalProduct::class, 'shop_id');
    }
}
