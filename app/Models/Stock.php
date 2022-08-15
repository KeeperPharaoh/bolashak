<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable;

class Stock extends Model
{
    use HasFactory, Translatable;

    protected array $translatable = [
        'title',
        'description'
    ];
}
