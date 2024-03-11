<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameChangeOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
           Schema::table('orders', function (Blueprint $table) {
              $table->renameColumn('change','price_change');
           });
    }
    public function down()
    {
           Schema::table('orders', function (Blueprint $table) {
                 $table->renameColumn('price_change','change');
           });
    }
}
