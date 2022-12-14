<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionAndInstructionToRegularCategoryTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regular_category_tests', function (Blueprint $table) {
            $table->text('description')
                  ->nullable()
            ;
            $table->text('instruction')
                  ->nullable()
            ;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('regular_category_tests', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('instruction');
        });
    }
}
