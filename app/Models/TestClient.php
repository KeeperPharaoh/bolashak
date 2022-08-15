<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'result',
        'user_id',
        'test_id',
    ];
}
