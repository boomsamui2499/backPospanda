<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeNullableOnSupplierTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('supplier', function (Blueprint $table) {

            $table->string('phone_number')->nullable()->change();
            $table->string('registered_date')->nullable()->change();
            $table->string('address_line1')->nullable()->change();
            $table->string('address_line2')->nullable()->change();
            $table->string('province')->nullable()->change();
            $table->string('zip_code')->nullable()->change();
            $table->string('tax_registered_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->string('phone_number')->nullable(false)->change();
            $table->string('registered_date')->nullable(false)->change();
            $table->string('address_line1')->nullable(false)->change();
            $table->string('address_line2')->nullable(false)->change();
            $table->string('province')->nullable(false)->change();
            $table->string('zip_code')->nullable(false)->change();
            $table->string('tax_registered_number')->nullable(false)->change();
        });
    }
}
