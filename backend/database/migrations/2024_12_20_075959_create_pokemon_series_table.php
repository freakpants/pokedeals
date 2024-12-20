<?php

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
        Schema::create('pokemon_series', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name_en')->nullable();
            $table->string('name_de')->nullable();
            $table->string('name_ja')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pokemon_series');
    }
};
