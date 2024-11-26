<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SetController extends Controller
{
    public function index()
    {
        // Fetch all sets
        $sets = DB::table('pokemon_sets')
            ->orderBy('release_date', 'asc')
            ->get();

        return response()->json($sets);
    }
}
