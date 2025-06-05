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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

Route::post('/update-stock', [StockController::class, 'store']);

Route::get('/products', [PokemonProductController::class, 'index']);
Route::get('/shops', [ShopController::class, 'index']);

Route::get('/sets', [SetController::class, 'index']);
Route::get('/series', [SerieController::class, 'index']);
Route::get('/product_types', [ProductTypeController::class, 'index']);

Route::get('/optimal-zip', [StockAnalysisController::class, 'getOptimalZipCode']);

Route::get('/outdated-cost-centers', [OutdatedCostCentersController::class, 'store']);



Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'token' => $user->createToken('api-token')->plainTextToken,
    ]);
});

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

?>
