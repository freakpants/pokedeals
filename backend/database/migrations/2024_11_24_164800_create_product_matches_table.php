<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductMatchesTable extends Migration
{
    public function up()
    {
        Schema::create('product_matches', function (Blueprint $table) {
            $table->id();
            $table->string('local_sku');
            $table->string('external_id');
            $table->string('title');
            $table->string('price')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable(); // Add shop info if needed

            $table->unique(['local_sku', 'external_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_matches');
    }
}
