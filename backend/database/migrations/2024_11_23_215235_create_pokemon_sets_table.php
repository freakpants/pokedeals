<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('pokemon_sets', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique(); // Generic identifier (e.g., 'surging_sparks')
            $table->string('title_en')->default('');             // English title
            $table->string('title_de')->default(''); ;             // German title
            $table->string('title_jp')->default(''); ;             // Japanese title
            $table->string('shortcode')->default('')->unique();  // Shortcode (e.g., 'sv08')
            $table->string('card_code_en')->default(''); ;         // Card code for English (e.g., 'SSP')
            $table->string('card_code_de')->default(''); ;         // Card code for German
            $table->string('card_code_jp')->default(''); ;         // Card code for Japanese
        });
    }

    public function down()
    {
        Schema::dropIfExists('pokemon_sets');
    }
};
