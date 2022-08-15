<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class StudyingProgram extends Model
{
    use HasFactory, Translatable;

    protected array $translatable = [
        'title',
        'age',
        'date',
        'description',
        'second_description'
    ];
}
