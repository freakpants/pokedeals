<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ShopTypes;

class CreateExternalShopsTable extends Migration
{
    public function up()
    {
        Schema::create('external_shops', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('base_url')->unique();
            $table->enum('shop_type', ShopTypes::getValues())->default(ShopTypes::Other->value);
            $table->integer('previous_last_page')->default(1);
            // category url (can be empty)
            $table->json('category_urls')->nullable();
            $table->timestamp('last_scraped_at')->nullable();
            $table->string('image')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_shops');
    }
}
?>