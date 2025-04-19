<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StockChangesController extends Controller
{
    public function getStockChanges($productId = null)
{
    try {
        $productId = $productId ?? '100119063'; // default to Mini Tins

        $stockChanges = DB::table('stock_data')
            ->join('migros_stores', 'stock_data.store_id', '=', 'migros_stores.store_id')
            ->select(
                'stock_data.store_id',
                'stock_data.product_id',
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
            ->where('stock_data.product_id', $productId)
            ->where('stock_data.timestamp', '>=', '2025-04-15 00:01:00')
            ->orderBy('stock_data.timestamp', 'desc')
            ->get();

        $stockChanges->each(function ($stockChange) {
            $stockChange->timestamp = date('Y-m-d H:i:s', strtotime($stockChange->timestamp . ' +2 hour'));
        });

        return view('stock_changes', compact('stockChanges','productId'));
    } catch (\Exception $e) {
        return response()->json(['error' => 'An error occurred', 'details' => $e->getMessage()], 500);
    }
}

}
