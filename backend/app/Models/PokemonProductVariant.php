<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PokemonProductVariant extends Model
{

    protected $fillable = [
        'id',
        'product_type'
    ];

    // disable timestamps
    public $timestamps = false;

    // Relationship: Pokemon Product Variant belongs to a Product Type.
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

}
