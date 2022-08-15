<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class Review extends Model
{
    use HasFactory, Translatable;

    protected array $translatable = [
        'full_name',
        'description',
        'before',
        'after'
    ];
}
