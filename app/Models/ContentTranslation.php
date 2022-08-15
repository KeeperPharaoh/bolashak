<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class ContentTranslation extends Model
{
    use HasFactory, Translatable;

    protected array $translatable = [
        'get_tested',
        'request_a_call',
        'do_you_want_to_determine_your_level',
        'click_here',
        'our_training_programs',
        'more',
        'submit_your_application',
        'question_answer',
        'subtitle_question_answer',
        'feedback_from_our_students',
        'fill_out_the_form_and_we_will_contact_you',
        'universities_partners',
        'learning_is_easier_with_us',
        'all_rights_reserved',
        'social_networks',
        'contacts',
        'the_address',
        'write_whatsapp',
        'days',
        'hours',
        'minutes',
        'seconds',
        'stocks_end_text',
        'before',
        'after',
        'callback',
        'name',
        'phone',
        'email',
        'about_company',
        'program',
        'reviews',
        'partners',
		 'menu_list',
        'why_choose_us',
    ];
}
