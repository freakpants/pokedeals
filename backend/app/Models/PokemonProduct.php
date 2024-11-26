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

    // disable timestamps
    public $timestamps = false;

    protected $fillable = [
        'title',
        'sku',
        'price',
        'type',
        'set_identifier',
        'product_url',
        'images',
    ];

    protected $casts = [
        'images' => 'array', // Cast the JSON field to an array
    ];

    public function matches()
    {
        return $this->hasMany(ProductMatch::class, 'local_sku', 'sku');
    }
}

?>