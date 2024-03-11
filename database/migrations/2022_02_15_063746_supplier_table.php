<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SupplierTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier', function (Blueprint $table) {
            $table->increments('supplier_id');
            $table->string('Firstname');
            $table->string('Lastname');
            $table->string('Nickname')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone_number');
            $table->string('registered_date');
            $table->string('address_line1');
            $table->string('address_line2');
            $table->string('province');
            $table->string('zip_code');
            $table->string('tax_registered_number'); //เลขประจำตัวผู้เสียภาษี 
            $table->string('company_name')->nullable(); //ชื่อบริษัท 
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
