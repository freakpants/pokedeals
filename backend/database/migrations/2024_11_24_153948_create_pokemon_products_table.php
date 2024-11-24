<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePokemonProductsTable extends Migration
{
    public function up()
    {
        Schema::create('pokemon_products', function (Blueprint $table) {
            $table->string('sku')->primary(); // Use SKU as the primary key
            $table->string('title');
            $table->string('price')->nullable();
            $table->text('product_url');
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pokemon_products');
    }
}

?>
