<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StockChangesController;

Route::get('/', function () {
    return response()->json(['message' => 'API is working!']);
});

Route::get('/stock-changes', [StockChangesController::class, 'getStockChanges']);

Route::get('/reset-password/{token}', function ($token) {
    return response()->json([
        'message' => 'Frontend should handle reset for token: ' . $token
    ]);
})->name('password.reset');
