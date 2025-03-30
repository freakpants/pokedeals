<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('stock_data', function (Blueprint $table) {
            $table->id();
            $table->string('store_id'); // Store ID from JSON keys
            $table->string('product_id'); // Product ID inside availabilities
            $table->integer('stock')->default(0); // Stock level
            $table->integer('change')->nullable(); // change to previous stock level
            $table->timestamp('timestamp')->nullable(); // Timestamp of last update
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_data');
    }
};
