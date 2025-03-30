<?php

use App\Http\Controllers\Api\PokemonProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SetController;
use App\Http\Controllers\Api\SerieController;
use App\Http\Controllers\Api\ProductTypeController;
use App\Http\Controllers\Api\StockAnalysisController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\OutdatedCostCentersController;

Route::post('/update-stock', [StockController::class, 'store']);

Route::get('/products', [PokemonProductController::class, 'index']);
Route::get('/shops', [ShopController::class, 'index']);

Route::get('/sets', [SetController::class, 'index']);
Route::get('/series', [SerieController::class, 'index']);
Route::get('/product_types', [ProductTypeController::class, 'index']);

Route::get('/optimal-zip', [StockAnalysisController::class, 'getOptimalZipCode']);

Route::get('/outdated-cost-centers', [OutdatedCostCentersController::class, 'store']);

?>
