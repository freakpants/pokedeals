<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProductTypes; 

class CreatePokemonProductsTable extends Migration
{
    public function up()
    {
        Schema::create('pokemon_products', function (Blueprint $table) {
            $table->string('sku')->primary(); // Use SKU as the primary key
            $table->string('title');
            $table->string('price')->nullable();
            $table->enum('type', ProductTypes::getValues())->default(ProductTypes::Other->value);
            // a foreign key to the pokemon_sets table
            $table->string('set_identifier')->nullable();
            // create the relationship to the pokemon_sets table
            $table->foreign('set_identifier')->references('set_identifier')->on('pokemon_sets')->onDelete('set null');
            $table->string('variant')->nullable();
            $table->text('product_url');
            $table->json('images')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pokemon_products');
    }
}

?>
