<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditHistory extends Model
{
    use HasFactory;

    protected $table = 'credit_history';

    protected $fillable = [
        'user_cpf',
        'instituition_id',
        'modality',
        'value_requested',
        'installments',
    ];
}
