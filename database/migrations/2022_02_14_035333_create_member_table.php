<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('member', function (Blueprint $table) {
            $table->increments('member_id');
            $table->string('Firstname');
            $table->string('Lastname');
            $table->string('Nickname')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone_number');
            $table->string('member_code');
            $table->date('birthdate');
            $table->date('registered_date');
            $table->string('address_line1');
            $table->string('address_line2');
            $table->string('province');
            $table->string('zip_code');
            $table->float('debt', 15, 5)->default(0);
            $table->integer('loyalty_point')->default(1);
            $table->string('line_id')->nullable();
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
        Schema::dropIfExists('member');
    }
}
