<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instituitions extends Model
{
    use HasFactory;


    protected $table = 'instituitions';

    protected $fillable = [
        'id',
        'nome',
    ];

    public $incrementing = false;

    protected $keyType = 'integer';
}
