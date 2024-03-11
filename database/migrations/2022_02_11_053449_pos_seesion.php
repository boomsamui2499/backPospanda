<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PosSeesion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pos_session', function (Blueprint $table) {
            $table->increments('pos_session_id');
            $table->string('pos_session_name');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->dateTime('open_datetime');
            $table->dateTime('close_datetime')->nullable();
            $table->float('open_cash_amount', 15, 5);
            $table->float('close_cash_amount', 15, 5)->nullable();
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
        Schema::dropIfExists('pos_session');
    }
}
