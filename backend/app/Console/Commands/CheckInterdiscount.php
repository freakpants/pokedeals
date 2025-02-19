<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckInterdiscount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the Interdiscount website for new products';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->call('app:check-shop', ['shopType' => 'interdiscount']);
    }
}
