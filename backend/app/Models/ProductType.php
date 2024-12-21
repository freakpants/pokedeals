<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    protected $fillable = [
        'product_type',
    ];

    // disable timestamps
    public $timestamps = false;

}
