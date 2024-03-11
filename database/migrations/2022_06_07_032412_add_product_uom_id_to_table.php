<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductUomIdToTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_order_line', function (Blueprint $table) {
            $table->integer('product_uom_id')->unsigned()->nullable();
            $table->foreign('product_uom_id')->references('product_uom_id')->on('product_uom');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_order_line', function (Blueprint $table) {
            $table->dropForeign(['product_uom_id']);
            $table->dropColumn('product_uom_id');
        });
    }
}
