<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProductTypes;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pokemon_product_variants', function (Blueprint $table) {
            $table->id();
            $table->string('en_name')->nullable();
            $table->string('en_short')->nullable();
            // foreign key to product_types
            $table->enum('product_type', ProductTypes::getValues())->default(ProductTypes::Other->value);
            $table->json('de_strings')->nullable();
            $table->json('en_strings')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemon_product_variants');
    }
};
