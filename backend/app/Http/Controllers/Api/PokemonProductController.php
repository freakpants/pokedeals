<?php
namespace App\Http\Controllers\Api;

use App\Models\PokemonProduct;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PokemonProductController extends Controller
{
    public function index()
    {
        return response()->json(PokemonProduct::all());
    }
}

?>