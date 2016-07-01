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
            $table->enum('operation', ['deposit', 'withdraw']);     # Tipo de operación (Ingreso o Retiro de dinero) 
            $table->string('concept');                              # Concepto de operación (Nueva Venta, Ingreso de Cambio, Gasto)
            $table->integer('model')->nullable();                   # Model (Sale, Expense)
            $table->integer('model_id')->nullable();                # Record Id

            $table->integer('payment_id')->nullable();              # Metodo de pago (Model PayMethod) 
            $table->decimal('amount', 10, 2);                       # Monto ingresado o retirado

            $table->longText('description')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_stockist_tills');
    }
}
