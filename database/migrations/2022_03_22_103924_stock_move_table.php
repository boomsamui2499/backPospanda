<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StockMoveTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_move', function (Blueprint $table) {
            $table->increments('stock_move_id');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('product_id')->on('product');
            $table->string('ref_type');
            $table->string('ref_id');
            $table->integer('qty');
            $table->dateTime('create_datetime');
            $table->dateTime('updated_at')->nullable();
            $table->integer('active')->default(1);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_move');
    }
}
