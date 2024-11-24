<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalShopsTable extends Migration
{
    public function up()
    {
        Schema::create('external_shops', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('base_url')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_shops');
    }
}
?>