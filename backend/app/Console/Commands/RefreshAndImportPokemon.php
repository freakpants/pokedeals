<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RefreshAndImportPokemon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pokemon:refresh-and-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback migrations, migrate, seed the database, and import Pokémon products from JSON';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Rolling back all migrations...');
        $this->call('migrate:rollback', ['--force' => true]);

        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        $this->info('Seeding the database...');
        $this->call('db:seed', ['--force' => true]); 

        $this->info('Importing Pokémon products...');
        // Define the files to import
        $filePaths = [
            storage_path('pokemon.json'),
        ];

        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                $this->info("Importing from file: {$filePath}");
                $this->call('pokemon:import', ['file' => $filePath]);
            } else {
                $this->error("File not found: {$filePath}");
            }
        }

        $this->info('All operations completed successfully!');
        return Command::SUCCESS;
    }
}
