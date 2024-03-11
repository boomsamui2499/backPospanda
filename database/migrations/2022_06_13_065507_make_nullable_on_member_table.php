<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeNullableOnMemberTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('member', function (Blueprint $table) {
            $table->date('birthdate')->nullable()->change();
            $table->date('registered_date')->nullable()->change();
            $table->string('address_line1')->nullable()->change();
            $table->string('address_line2')->nullable()->change();
            $table->string('province')->nullable()->change();
            $table->string('zip_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('member', function (Blueprint $table) {
            $table->date('birthdate')->nullable(false)->change();
            $table->date('registered_date')->nullable(false)->change();
            $table->string('address_line1')->nullable(false)->change();
            $table->string('address_line2')->nullable(false)->change();
            $table->string('province')->nullable(false)->change();
            $table->string('zip_code')->nullable(false)->change();
        });
    }
}
