<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->string('get_tested');
            $table->string('request_a_call');
            $table->string('do_you_want_to_determine_your_level');
            $table->string('click_here');
            $table->string('our_training_programs');
            $table->string('more');
            $table->string('submit_your_application');
            $table->string('question_answer');
            $table->string('subtitle_question_answer');
            $table->string('feedback_from_our_students');
            $table->string('fill_out_the_form_and_we_will_contact_you');
            $table->string('universities_partners');
            $table->string('learning_is_easier_with_us');
            $table->string('all_rights_reserved');
            $table->string('social_networks');
            $table->string('contacts');
            $table->string('the_address');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_translations');
    }
}
