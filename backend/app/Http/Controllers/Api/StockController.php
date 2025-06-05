<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\StockData;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Store incoming stock data.
     */
    public function store(Request $request)
    {
        // Log::info('Raw Request Body:', ['body' => $request->getContent()]);

        $data = $request->json()->all(); // Ensure JSON parsing

        // Log::info('Parsed stock data:', ['data' => $data]);

        try {
            // Validate the incoming data structure
            $validated = validator($data, [
                'catalogItemId' => 'required|integer',
                'availabilities' => 'required|array',
                'availabilities.*.id' => 'required|string',
                'availabilities.*.stock' => 'required|integer',
            ])->validate();

            $catalogItemId = $validated['catalogItemId'];
            $availabilities = $validated['availabilities'];

            foreach ($availabilities as $stockItem) {
                $storeId = $stockItem['id'];
                $stock = $stockItem['stock'];

                // ✅ Update the `updated_at` field in `migros_stores`
                DB::table('migros_stores')
                    ->where('store_id', $storeId)
                    ->update(['updated_at' => now()]);

                // ✅ Load the latest stock data entry for this store
                $latestData = StockData::where('product_id', $catalogItemId)
                    ->where('store_id', $storeId)
                    ->orderBy('timestamp', 'desc')
                    ->first();

                // ✅ If the stock is unchanged, do nothing more
                if ($latestData && $latestData->stock === $stock) {
                    continue;
                }

                $change = $latestData ? $stock - $latestData->stock : 0;

                // ✅ Create new stock data entry
                StockData::create([
                    'product_id' => $catalogItemId,
                    'store_id' => $storeId,
                    'stock' => $stock,
                    'timestamp' => now(),
                    'change' => $change,
                ]);
            }

            return response()->json(['message' => 'Stock data stored successfully'], 201);
        } catch (\Exception $e) {
            Log::error('Stock data import failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Stock data import failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
