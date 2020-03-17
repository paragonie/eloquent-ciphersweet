<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBlindIndexesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::connection(config('ciphersweet.database_connection'))->create(config('ciphersweet.table_name'), function (Blueprint $table) {
            $table->string('type');
            $table->string('value');
            $table->unsignedBigInteger('foreign_id');

            $table->index(['type', 'value']);
            $table->unique(['type', 'foreign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::connection(config('ciphersweet.database_connection'))->dropIfExists(config('ciphersweet.table_name'));
    }
}
