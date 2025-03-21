<?php

use App\Http\Controllers\Api\PokemonProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SetController;
use App\Http\Controllers\Api\SerieController;
use App\Http\Controllers\Api\ProductTypeController;

Route::get('/products', [PokemonProductController::class, 'index']);
Route::get('/shops', [ShopController::class, 'index']);

Route::get('/sets', [SetController::class, 'index']);
Route::get('/series', [SerieController::class, 'index']);
Route::get('/product_types', [ProductTypeController::class, 'index']);


?>
