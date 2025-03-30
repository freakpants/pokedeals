<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockData extends Model
{
    use HasFactory;

    protected $table = 'stock_data';

    protected $fillable = [
        'product_id',
        'store_id',
        'stock',
        'timestamp',
        'change',
    ];

    public $timestamps = false;
}
