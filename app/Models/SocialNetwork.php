<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class SocialNetwork extends Model
{
    use HasFactory, Translatable;

	    protected array $translatable = ['link'];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
