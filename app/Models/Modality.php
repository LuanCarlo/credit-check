<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modality extends Model
{
    use HasFactory;

    protected $table = 'modality';

    protected $fillable = [
        'id',
        'nome',
    ];

    public $incrementing = false;

    protected $keyType = 'string';
}
