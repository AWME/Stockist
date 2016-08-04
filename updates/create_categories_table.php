<?php namespace AWME\Stockist\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateCategoriesTable extends Migration
{

    public function up()
    {
        Schema::create('awme_stockist_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->index();
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('awme_stockist_categories');
    }
}
