<?php namespace AWME\Stockist\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateProductsTable extends Migration
{

    public function up()
    {
        Schema::create('awme_stockist_products', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('category_id')->nullable();
            $table->string('sku')->unique();
            $table->string('name')->index();
            $table->string('slug')->index()->unique();
            $table->longText('description');
            $table->longText('tags')->nullable();
            $table->decimal('price_cost', 10, 2)->default(0)->nullable();
            $table->decimal('price_sale', 10, 2)->default(0)->nullable();
            $table->decimal('iva', 10, 2)->default(0)->nullable();
            $table->integer('stock')->default(0)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_stockable')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_stockist_products');
    }
}
