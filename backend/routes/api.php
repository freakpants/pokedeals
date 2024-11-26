<?php

use App\Http\Controllers\Api\PokemonProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ShopController;

Route::get('/products', [PokemonProductController::class, 'index']);
Route::get('/shops', [ShopController::class, 'index']);


?>
