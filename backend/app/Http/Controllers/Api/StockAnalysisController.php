<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockAnalysisController extends Controller
{
    public function getOptimalZipCode()
    {
        try {
            // Step 1: Identify stores that haven't been updated in the last 10 minutes
            $outdatedStores = DB::table('migros_stores')
                ->select('store_id', 'updated_at')
                ->where('updated_at', '<', Carbon::now()->subMinutes(12)) // Exclude recently updated stores
                // ->orWhereNull('updated_at') // Include stores that have never been updated
                ->orderBy('updated_at', 'asc') // Prioritize the oldest updates first
                ->limit(1)
                ->get();

            // Extract store IDs
            $storeIds = $outdatedStores->pluck('store_id');

            // Step 2: Determine the best zip to update
            $zipCounts = DB::table('migros_stores')
                ->whereIn('store_id', $storeIds)
                ->pluck('triggered_by') // Get the triggered_by JSON column
                ->map(fn($json) => json_decode($json, true) ?? []) // Convert JSON safely
                ->flatten()
                ->countBy()
                ->sortDesc();

            // Step 3: Select the most relevant zip code
            $optimalZip = $zipCounts->keys()->first() ?? '8600'; // Default zip if none found

            return response()->json(['zip' => (string) $optimalZip]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
