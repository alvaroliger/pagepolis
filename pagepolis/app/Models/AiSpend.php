<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSpend extends Model
{
    protected $table = 'ai_spend';

    protected $fillable = ['day', 'tokens_in', 'tokens_out', 'est_cost_usd'];

    // 'day' se guarda como string 'Y-m-d' (sin cast date) para que las
    // comparaciones por día del guardián de presupuesto sean exactas.
    protected $casts = [
        'tokens_in'    => 'integer',
        'tokens_out'   => 'integer',
        'est_cost_usd' => 'float',
    ];
}
