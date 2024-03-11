<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Order extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('order_id');
            $table->string('order_number'); //2022/02/001 รันตามเดือน
            $table->float('subtotal', 15, 5);
            $table->float('vat', 15, 5);
            $table->float('total', 15, 5);
            $table->float('total_payment', 15, 5);
            $table->float('total_recive', 15, 5);
            $table->float('total_margin', 15, 5);
            $table->float('change', 15, 5);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');;
            $table->integer('member_id')->unsigned()->nullable();
            $table->foreign('member_id')->references('member_id')->on('member');
            $table->integer('pos_session_id')->unsigned();
            $table->foreign('pos_session_id')->references('pos_session_id')->on('pos_session');
            $table->dateTime('created_datetime');
            $table->string('is_vat')->default(0);
            $table->string('type')->default(1);
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
