<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SerieController extends Controller
{
    public function index()
    {
        // Fetch all series
        $series = DB::table('pokemon_series')
            ->get();

        return response()->json($series);
    }
}
