<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpdatedToNoteCoffeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('note_coffee', function (Blueprint $table) {
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('note_coffee', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
}
