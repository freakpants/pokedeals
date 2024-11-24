<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->string('url')->nullable();
            $table->json('metadata')->nullable(); // For additional data like tags or images
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('external_shops')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_products');
    }
}
