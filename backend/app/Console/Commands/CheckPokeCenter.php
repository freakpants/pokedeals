<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use Exception;

class CheckPokeCenter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-poke-center {--force-email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $forceEmail =  $this->option('force-email') ?? false;

        $url = "https://api.scraperapi.com/?api_key=36ab00a69c3659025119051957dac92a&url=https%3A%2F%2Fwww.pokemoncenter.com%2Fen-gb%2Fcategory%2Ftrading-card-game";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        if (empty($response)) {
            echo "Error: Response is empty or invalid." . PHP_EOL;
            exit; // Exit if response is empty
        }

        $domail = false;
        if($forceEmail){
            $domail = true;
        }
        $headers = "From: pokedeals";
        $to = "freakpants@gmail.com";

        try {
            // Load the response into DomCrawler
            $crawler = new Crawler($response);
        
            // Select the <h2> element with the desired class (assuming class isn't static)
            $categorySubtitle = $crawler->filter('h2')->reduce(function ($node) {
                return str_contains($node->text(), 'Products:');
            });
        
            if ($categorySubtitle->count() > 0) {
                // Extract the raw text
                $rawText = $categorySubtitle->text();
        
                // Use regex to extract the total count
                if (preg_match('/of\s+(\d+)/', $rawText, $matches)) {
                    $totalProducts = $matches[1]; // The total is captured in group 1
                    echo "Total Products: " . $totalProducts . PHP_EOL;
                    // if the count isnt 603, send an email to freakpants@gmail.com
                    // Send email

                    $previousCount = 608;

                    if ($totalProducts != $previousCount) {
                        $domail = true;
                        if($totalProducts > $previousCount){
                            $subject = "Pokemon Center has more than " . $previousCount . " TCG Products";
                        } else {
                            $subject = "Pokemon Center has less than " . $previousCount . " TCG Products";
                        }
                        $message = "The total product count is not " . $previousCount . " It is currently " . $totalProducts;

                    } else {
                        // $domail = true;
                        $subject = "Pokemon Center: Total product count is " . $totalProducts;
                        $message = "No changes on Pokemon Center";
                    }

                } else {
                    $domail = true;
                    echo "Total product count not found in subtitle." . PHP_EOL;
                    $subject = "Pokemon Center product count not found";
                    $message = "The total product count was not found in the subtitle.";
                }
                
            } else {
                $domail = true;
                echo "Category subtitle not found." . PHP_EOL;
                $subject = "Pokemon Center category subtitle not found";
                $message = "The category subtitle was not found.";
            }


        } catch (Exception $e) {
            $domail = true;
            echo "Error: " . $e->getMessage() . PHP_EOL;
            $subject = "Pokemon Center: Error in script";
            $message = "An error occurred in the script: " . $e->getMessage();
        }

        if($domail){
            mail($to, $subject, $message, $headers);
        }

    } 
}
