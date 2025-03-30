<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportMigrosStores extends Command
{
    protected $signature = 'import:migros-stores';
    protected $description = 'Import Migros store data from a JSON file into the migros_stores table';

    public function handle()
    {
        $filePath = storage_path('migros_stores_with_queries.json');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }

        $jsonData = json_decode(file_get_contents($filePath), true);

        if (!$jsonData) {
            $this->error("Invalid JSON format.");
            return 1;
        }

        $insertData = [];

        foreach ($jsonData as $storeId => $storeDetails) {
            $info = $storeDetails['info'] ?? null;
            if (!$info) {
                continue;
            }

            $insertData[] = [
                'store_id' => $storeId,
                'name' => $info['name'],
                'address' => $info['address'],
                'city' => $info['city'],
                'zip' => $info['zip'],
                'latitude' => $info['latitude'],
                'longitude' => $info['longitude'],
                'triggered_by' => json_encode($storeDetails['triggered_by'] ?? []),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('migros_stores')->upsert($insertData, ['store_id']);

        $this->info("Migros store data imported successfully!");
        return 0;
    }
}
