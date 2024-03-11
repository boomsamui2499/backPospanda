<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatedToOutOfStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('out_of_stock', function (Blueprint $table) {

                $table->increments('out_of_stock_id');
                $table->integer('product_id')->unsigned();
                $table->foreign('product_id')->references('product_id')->on('product');
                $table->integer('out_of_stock_qty')->nullable();
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
        Schema::table('out_of_stock', function (Blueprint $table) {
            //
        });
    }
}
