<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StockChangesController extends Controller
{
    public function getStockChanges()
    {
        try {
            $stockChanges = DB::table('stock_data')
                ->join('migros_stores', 'stock_data.store_id', '=', 'migros_stores.store_id')
                ->select(
                    'stock_data.store_id',
                    'migros_stores.name as store_name',
                    'migros_stores.address',
                    'migros_stores.city',
                    'migros_stores.zip',
                    'migros_stores.latitude',
                    'migros_stores.longitude',
                    'stock_data.stock',
                    'stock_data.change',
                    'stock_data.timestamp'
                )
                ->orderBy('stock_data.timestamp', 'desc')
                ->get();

            // increase timestamp by one hour for all of them 
            $stockChanges->each(function ($stockChange) {
                $stockChange->timestamp = date('Y-m-d H:i:s', strtotime($stockChange->timestamp . ' +2 hour'));
            });

            return view('stock_changes', compact('stockChanges'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred', 'details' => $e->getMessage()], 500);
        }
    }
}
