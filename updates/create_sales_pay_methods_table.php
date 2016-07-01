<?php namespace AWME\Stockist\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateSalesPayMethodsTable extends Migration
{

    public function up()
    {
        Schema::create('awme_stockist_sales_pay_methods', function ($table) {
            
            $table->engine = 'InnoDB';
            $table->increments('id');

            $table->integer('sale_id');
            $table->integer('pay_method_id');
            $table->index(['sale_id', 'pay_method_id']);

            $table->decimal('concept', 10, 2)->nullable();      # Concepto de pago por total
            $table->decimal('total', 10, 2)->nullable();        # Total, según los taxes del paymethod

            $table->longText('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_stockist_sales_pay_methods');
    }
}