<?php namespace AWME\Stockist\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePayMethodsTable extends Migration
{

    public function up()
    {
        Schema::create('awme_stockist_pay_methods', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name');                             # Efectivo, Debito, Cta. Corriente, Tarjeta de Crédito
            $table->string('code')->unique();                   # AR, CTA, CTE, TDC
            $table->text('description')->nullable();
            $table->string('tax_type');                         # tipo Recargo/Descuento (%,$)
            $table->decimal('tax_amount', 10, 2)->nullable();               # Recargo/Descuento

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_stockist_pay_methods');
    }
}
