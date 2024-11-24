<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PokemonProduct extends Model
{
    use HasFactory;

    protected $primaryKey = 'sku'; // Set SKU as the primary key
    public $incrementing = false; // Disable auto-incrementing since SKU is a string
    protected $keyType = 'string'; // Specify the primary key type

    protected $fillable = [
        'title',
        'sku',
        'price',
        'product_url',
        'images',
    ];

    protected $casts = [
        'images' => 'array', // Cast the JSON field to an array
    ];
}

?>