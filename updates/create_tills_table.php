<?php namespace AWME\Stocket\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateTillsTable extends Migration
{

    public function up()
    {
        Schema::create('awme_stockist_tills', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('seller_id');

            /**
             * Caja registradora
             */
            $table->enum('operation', ['deposit', 'withdraw','till_report']);     # Tipo de operación (Ingreso o Retiro de dinero) 
            $table->string('concept');                              # Concepto de operación (Nueva Venta, Ingreso de Cambio, Gasto)
            $table->longText('description')->nullable();            # Descripción de movimiento
            
            $table->longText('record_data')->nullable();            # Controller, Model, RecordId etc.

            $table->decimal('amount', 10, 2);                       # Monto ingresado o retirado


            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_stockist_tills');
    }
}
