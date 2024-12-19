<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\ProductTypes;

class CreateExternalProductsTable extends Migration
{
    public function up()
    {
        Schema::create('external_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id'); // Foreign key to external_shops
            $table->string('external_id')->unique(); // External product ID or SKU
            $table->string('title');
            $table->string('price')->nullable();
            $table->integer('stock')->nullable();
            $table->integer('multiplier')->default(1);
            $table->string('url')->nullable();
            $table->enum('type', ProductTypes::getValues())->default(ProductTypes::Other->value);
            // a foreign key to the pokemon_sets table
            $table->string('set_identifier')->nullable();
            // create the relationship to the pokemon_sets table
            $table->foreign('set_identifier')->references('set_identifier')->on('pokemon_sets')->onDelete('set null');
            // language is a 2 letter code
            $table->string('language')->nullable();
            $table->json('metadata')->nullable(); // For additional data like tags or images
            $table->foreign('shop_id')->references('id')->on('external_shops')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_products');
    }
}