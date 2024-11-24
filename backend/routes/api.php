<?php

use App\Http\Controllers\Api\PokemonProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [PokemonProductController::class, 'index']);

?>
