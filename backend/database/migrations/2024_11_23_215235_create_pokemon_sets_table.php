<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('pokemon_sets', function (Blueprint $table) {
            // set identifier is the primary key, its a string
            $table->string('set_identifier')->primary();
            // id is the tcgdex id, its a string
            $table->string('id');

            // series is the generation the set belongs to, its a string
            $table->string('series');

            // title_de is the german title, its a string
            $table->string('title_de')->nullable();
            // title_en is the english title, its a string
            $table->string('title_en')->nullable();
            // title_ja is the japanese title, its a string
            $table->string('title_ja')->nullable();
            // release_date is the date the set was released, its a date
            $table->date('release_date');

        });
    }

    public function down()
    {
        Schema::dropIfExists('pokemon_sets');
    }
};
