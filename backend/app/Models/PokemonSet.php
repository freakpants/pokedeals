<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PokemonSet extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'identifier',
        'title_en',
        'title_de',
        'title_jp',
        'shortcode',
        'card_code_en',
        'card_code_de',
        'card_code_jp',
    ];
}
