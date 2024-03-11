<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangQtyTableStockAdjustmentLine extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stock_adjustment_line', function (Blueprint $table) {
            $table->float('computed_qty')->nullable()->change();
            $table->float('real_qty')->nullable()->change();
            $table->float('different_qty')->nullable()->change();
           });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stock_adjustment_line', function (Blueprint $table) {
            $table->float('computed_qty')->nullable(false)->change();
            $table->float('real_qty')->nullable(false)->change();
            $table->float('different_qty')->nullable(false)->change();
                });
    }
}
