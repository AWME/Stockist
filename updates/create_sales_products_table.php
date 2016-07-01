<?php namespace AWME\Stockist\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateSalesProductsTable extends Migration
{

    public function up()
    {
        Schema::create('awme_stockist_sales_products', function ($table) {
            
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->integer('sale_id');
            $table->integer('product_id');
            $table->index(['sale_id', 'product_id']);
            
            $table->decimal('quantity', 10, 2)->default(0)->nullable();

            $table->decimal('price', 10, 2)->default(0)->nullable();    # precio de venta
            $table->decimal('subtotal', 10, 2)->default(0)->nullable(); # price * quantity

            $table->longText('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_stockist_sales_products');
    }
}