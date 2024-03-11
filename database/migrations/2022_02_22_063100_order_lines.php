<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OrderLines extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_lines', function (Blueprint $table) {
            $table->increments('order_line_id');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('product_id')->on('product');
            $table->integer('order_id')->unsigned();
            $table->foreign('order_id')->references('order_id')->on('orders');
            $table->integer('product_uom_id')->unsigned()->nullable();
            $table->foreign('product_uom_id')->references('product_uom_id')->on('product_uom');
            $table->float('price', 15, 5)->nullable();
            $table->float('vat', 15, 5)->nullable();
            $table->float('margin', 15, 5)->nullable();
            $table->float('total_vat', 15, 5)->nullable();
            $table->float('total_margin', 15, 5)->nullable();
            $table->float('subtotal', 15, 5)->nullable();
            $table->float('total', 15, 5)->nullable();
            $table->integer('qty');
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
        //
    }
}
