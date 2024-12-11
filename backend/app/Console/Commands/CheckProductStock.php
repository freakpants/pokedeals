<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class CheckProductStock extends Command
{
    protected $signature = 'stock:check';
    protected $description = 'Check stock status of Prismatic Evolutions products via r.1lm.io and notify via email if back in stock';

    public function handle()
    {
        $url = 'https://r.1lm.io/p/https://www.pokemoncenter.com/en-gb/search/prismatic-evolutions';

        $recipient = 'freakpants@gmail.com';

        $attempts = 0;
        $maxAttempts = 5;
        $delay = 60; // seconds

        while ($attempts < $maxAttempts) {
            $response = Http::get($url);

            if ($response->status() === 200) {
                $this->info('Successfully fetched product data.');
                break;
            }

            if ($response->status() === 429) {
                $this->warn("Rate limited (429). Retrying in {$delay} seconds...");
                $attempts++;
                sleep($delay);
                continue;
            }

            $this->error('Failed to fetch product data. HTTP Status: ' . $response->status());
            return 1; // Command failed
        }

        if ($response->failed()) {
            $this->error('Exceeded max retries. Aborting.');
            return 1; // Command failed
        }

        $content = $response->body();

        $crawler = new Crawler($content);

        // Find all product entries
        $products = $crawler->filter('a')->each(function (Crawler $node) {
            // the link must contain "https://www.pokemoncenter.com/en-gb/product/"
            // we also need to look at the p above, if it includes 
            
        });

        // output a log line for each product
        foreach ($products as $product) {
            if ($product) {
                $status = $product['in_stock'] ? 'in stock' : 'out of stock';
                Log::info("Product '{$product['title']}' is {$status}.");
            }
        }


        // Define products and check their stock status
        $products = [
            'Elite Trainer Box' => 'https://www.pokemoncenter.com/en-gb/product/100-10019/pokemon-tcg-scarlet-and-violet-prismatic-evolutions-pokemon-center-elite-trainer-box',
            'Surprise Box' => 'https://www.pokemoncenter.com/en-gb/product/100-10096/pokemon-tcg-scarlet-and-violet-prismatic-evolutions-surprise-box',
        ];

        $backInStock = [];

        foreach ($products as $name => $link) {
            // Check if "SOLD OUT" text is absent for this product
            if (!str_contains($content, 'SOLD OUT')) {
                // log
                Log::info("Product '{$name}' is back in stock.");
                $backInStock[] = [
                    'name' => $name,
                    'link' => $link,
                ];
            } else {
                // log
                Log::info("Product '{$name}' is still out of stock.");
            }
        }

        $message = '';

        if (empty($backInStock)) {
            $this->info('No products are back in stock.');
            // save attempt to log
            Log::info('No products are back in stock.');
            return 0; // Command succeeded
        } 

        // Send email notification
        // log the products
        Log::info('Products back in stock: ' . json_encode($backInStock));

        Mail::raw($this->formatEmailContent($backInStock), function ($message) use ($recipient) {
            $message->to($recipient)
                ->subject('Prismatic Evolutions Products Back in Stock');
        });

        $this->info('Email sent for products back in stock.');
        return 0;
    }

    private function formatEmailContent(array $products): string
    {
        $content = "The following Prismatic Evolutions products are back in stock:\n\n";
        foreach ($products as $product) {
            $content .= "{$product['name']} - {$product['link']}\n";
        }
        return $content;
    }
}
