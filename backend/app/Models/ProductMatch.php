<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_sku',
        'external_id',
        'title',
        'price',
        'shop_id'
    ];

    // disable timestamps
    public $timestamps = false;

    /**
     * Relationship: Match belongs to a Pokémon product.
     */
    public function pokemonProduct()
    {
        return $this->belongsTo(PokemonProduct::class);
    }

    /**
     * Relationship: Match belongs to an external product.
     */
    public function externalProduct()
    {
        return $this->belongsTo(ExternalProduct::class, 'external_id', 'external_id');
    }

    /**
     * Relationship: Match belongs to a shop.
     */
    public function shop()
    {
        return $this->belongsTo(ExternalShop::class);
    }
}
