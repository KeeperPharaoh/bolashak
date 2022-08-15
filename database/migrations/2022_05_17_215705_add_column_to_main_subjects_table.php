<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToMainSubjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('main_subjects', function (Blueprint $table) {
            $table->string('title');
            $table->string('language');
            $table->string('type')->default('main_items');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('main_subjects', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->dropColumn('language');
            $table->dropColumn('type');
        });
    }
}
