<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StockAdjustmentLineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_adjustment_line', function (Blueprint $table) {
            $table->increments('stock_adjustment_line_id');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('product_id')->on('product');
            $table->integer('stock_adjustment_id')->unsigned();
            $table->foreign('stock_adjustment_id')->references('stock_adjustment_id')->on('stock_adjustment');
            $table->integer('computed_qty')->nullable();
            $table->integer('real_qty')->nullable();
            $table->integer('different_qty')->nullable();
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
        Schema::dropIfExists('stock_adjustment_line');
    }
}
