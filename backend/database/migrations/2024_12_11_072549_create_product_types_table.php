<?php

use App\Enums\ProductTypes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->enum('product_type', ProductTypes::getValues())->default(ProductTypes::Other->value)->primary();
            // number of packs
            $table->integer('pack_count')->nullable();
            // flag for whether the product can contain packs of different sets
            $table->boolean('mixed_sets')->default(false);
            // swh modifier => etbs in swh have -1 pack
            $table->integer('swh_modifier')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};
