<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class MainBlock extends Model
{
    use HasFactory, Translatable;

    protected $translatable = [
        'title',
        'subtitle',
        'image'
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at'
    ];
}
