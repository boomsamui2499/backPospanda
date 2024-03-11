<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTambonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('tambons', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('tambon')->nullable();
            $table->string('amphoe')->nullable();
            $table->string('province')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('tambon_code')->nullable();
            $table->string('amphoe_code')->nullable();
            $table->string('province_code')->nullable();
            $table->string('district')->nullable();
            $table->string('district_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tambons');
    }
}
