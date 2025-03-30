<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\StockData;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OutdatedCostCentersController extends Controller
{
    /**
     * Store incoming stock data.
     */
    public function store(Request $request)
    {
        // get the 10 store_id's from the migros_stores table that are the most outdated
        $outdatedCostCenters = DB::table('migros_stores')
            ->select('store_id')
            ->orderBy('updated_at', 'asc')
            ->limit(10)
            ->get();
        // return the store_id's as a JSON response
        return response()->json($outdatedCostCenters->pluck('store_id'));
    }
}
