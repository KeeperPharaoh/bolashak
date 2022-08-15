<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'language',
        'type',
        'test_id'
    ];
}
