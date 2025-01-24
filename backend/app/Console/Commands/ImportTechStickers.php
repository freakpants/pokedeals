<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportTechStickers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:techstickers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tech stickers from JSON and output total available products and number of stores';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {


        // surging sparks etb
        // $url = 'https://inventory.app.gcp.interdiscount.ch/v1/inventory?productId=14178694';

        // surging sparks booster
        // $url = 'https://inventory.app.gcp.interdiscount.ch/v1/inventory?productId=14178693';

        // crown zenith mini tin
        // $url = 'https://inventory.app.gcp.interdiscount.ch/v1/inventory?productId=13212281';

        // paldean fates mini tin
        // $url = 'https://inventory.app.gcp.interdiscount.ch/v1/inventory?productId=14040127';

        // 151 mini tin 
        // $url = 'https://inventory.app.gcp.interdiscount.ch/v1/inventory?productId=13927201';

        // tech stickers
        $url = 'https://inventory.app.gcp.interdiscount.ch/v1/inventory?productId=14202215';
        $json = file_get_contents($url);
        $jsonData = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Error decoding JSON');
            return Command::FAILURE;
        }
        // get the json from https://inventory.app.gcp.interdiscount.ch/v1/inventory?productId=14202215
        

        $totalAvailable = 0;
        $storeCount = count($jsonData['shops']);

        foreach ($jsonData['shops'] as $shop) {
            $totalAvailable += $shop['available'];
        }

        $this->info("Total available products: {$totalAvailable}");
        $this->info("Number of stores: {$storeCount}");

        return Command::SUCCESS;
    }
}
