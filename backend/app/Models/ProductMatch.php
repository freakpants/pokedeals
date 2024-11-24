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
        'shop_id',
        'created_at',
        'updated_at',
    ];

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
        return $this->belongsTo(ExternalProduct::class);
    }

    /**
     * Relationship: Match belongs to a shop.
     */
    public function shop()
    {
        return $this->belongsTo(ExternalShop::class);
    }
}
