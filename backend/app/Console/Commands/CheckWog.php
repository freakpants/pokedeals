<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

class CheckWog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-wog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the Wog website for product count';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $domail = false;
        $headers = "From: pokedeals";
        $to = "freakpants@gmail.com";

        $response = Http::withHeaders([
            'Accept' => '*/*',
            'Content-Type' => 'multipart/form-data',
        ])->asForm()->post('https://www.wog.ch/index.cfm/ajax.productList', [
            'type' => 'Toys',
            'developerID' => '7688',
            'productTypeID' => '3',
            'productFormTypeName' => '',
            'displayTypeID' => '3',
            'listType' => 'developers',
            'maxRows' => '48',
            'page' => '1',
            'forceTileView' => 'false',
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            // Handle your response data
            // echo the amount of data['products']
            $productCount = count($data['products']);
            echo $productCount;
            if($productCount === 126){
                // $domail = true;
                $subject = "WOG: Product Count is 126";
                $message = "No changes on WOG";
            } else {
                $domail = true;
                $subject = "WOG: Product Count Changed";
                $message = "The product count on WOG has changed to " . $productCount;
            }
        } else {
            // Handle errors
            dd($response->status(), $response->body());
            $domail = true;
            $subject = "WOG: Error Occurred";
            $message = "An error occurred while checking the WOG website. Status: " . $response->status() . " Response: " . $response->body();
        }
        // do the actual email
        if ($domail) {
            mail($to, $subject, $message, $headers);
        }
    }
}
