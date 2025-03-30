<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportStockData extends Command
{
    protected $signature = 'import:stock-data';
    protected $description = 'Import stock data from a JSON file into the stock_data table';

    public function handle()
    {
        $filePath = storage_path('stock_data.json');       


        $jsonData = json_decode(file_get_contents($filePath), true);

        if (!$jsonData) {
            $this->error("Invalid JSON format.");
            return 1;
        }

        $insertData = [];

        foreach ($jsonData as $productId => $storeStock) {
            foreach ($storeStock['availabilities'] as $product) {
                $insertData[] = [
                    'store_id' => $product['id'],
                    'product_id' => $productId,
                    'stock' => $product['stock'],
                    'last_decrease_amount' => $product['lastDecreaseAmount'] ?? null,
                    'last_change' => $product['lastChange'] ?? null,
                    'timestamp' => $product['timestamp'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('stock_data')->insertOrIgnore($insertData);
        $this->info("Stock data imported successfully!");

        return 0;
    }
}
