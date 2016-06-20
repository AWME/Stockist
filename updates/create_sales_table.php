<?php namespace AWME\Stocket\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateSalesTable extends Migration
{

    public function up()
    {
        Schema::create('awme_stockist_sales', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');

            /**
             * Venta
             */
            $table->string('seller')->nullable();
            $table->longText('description')->nullable();
            
            $table->string('tax')->nullable();    # type json: [amount, type] 
            $table->decimal('subtotal', 10, 2)->default(0)->nullable();
            $table->decimal('total', 10, 2)->default(0)->nullable();

            $table->string('payment')->default('cash');
            $table->string('status')->default('open');

            /**
             * Facturación
             */
            $table->string('invoice')->nullable();
            $table->string('inv_type')->nullable();
            $table->string('inv_iva')->nullable();

            $table->string('fullname');
            $table->string('dni')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_stockist_sales');
    }
}
