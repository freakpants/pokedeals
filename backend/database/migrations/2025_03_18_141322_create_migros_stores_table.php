<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('migros_stores', function (Blueprint $table) {
            $table->string('store_id')->primary();
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->string('zip');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->json('triggered_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('migros_stores');
    }
};
