<?php
namespace App\Http\Controllers\Api;

use App\Models\PokemonProduct;
use App\Models\ProductMatch;
use App\Http\Controllers\Controller;

class PokemonProductController extends Controller
{
    public function index()
    {
        // Load products with their matches, filtering out those without matches
        $productsWithMatches = PokemonProduct::whereHas('matches')
            ->with('matches')
            ->get();

        return response()->json($productsWithMatches);
    }
}
