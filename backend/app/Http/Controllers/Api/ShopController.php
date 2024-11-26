<?php

namespace App\Http\Controllers\Api;

use App\Models\ExternalShop;
use App\Http\Controllers\Controller;

class ShopController extends Controller
{
    public function index()
    {
        // Fetch all shops
        $shops = ExternalShop::all();
        return response()->json($shops);
    }
}
