<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'result_id',
        'question',
        'points_awarded'
    ];
}
