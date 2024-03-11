<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metaData', function (Blueprint $table) {
            $table->increments('meta_id');
            $table->string('meta_module')->nullable();
            $table->string('meta_key')->nullable();
            $table->string('meta_value')->nullable();
            $table->integer('active')->default(1);
        });    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metaData');
    }
}
