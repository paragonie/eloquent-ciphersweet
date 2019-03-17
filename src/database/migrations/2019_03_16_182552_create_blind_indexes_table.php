<?php

namespace ParagonIE\EloquentCipherSweet\database\migrations;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

final class CreateBlindIndexesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blind_indexes', function (Blueprint $table) {
            $table->string('type');
            $table->string('value');
            $table->unsignedInteger('foreign_id');

            $table->index(['type', 'value']);
            $table->unique(['type', 'foreign_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('blind_indexes');
    }
}
