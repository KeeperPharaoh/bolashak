<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToContentTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('content_translations', function (Blueprint $table) {
            $table->string('write_whatsapp');
            $table->string('days');
            $table->string('hours');
            $table->string('minutes');
            $table->string('seconds');
            $table->string('stocks_end_text');
            $table->string('before');
            $table->string('after');
            $table->string('callback');
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('about_company');
            $table->string('program');
            $table->string('reviews');
            $table->string('partners');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('content_translations', function (Blueprint $table) {
            //
        });
    }
}
