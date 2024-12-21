<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ProductTypeController extends Controller
{
    public function index(){
        // Fetch all product types
        $productTypes = DB::table('product_types')
            ->get();

        return response()->json($productTypes);
    }
}

