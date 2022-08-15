<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Spatial;

class Coordinate extends Model
{
    use HasFactory, Spatial;

    /**
     * @var string[]
     */
    protected $spatial = ['latitude'];

    
    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];
}
