<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un movimiento del fondo de reserva de un dominio. Append-only: no se edita ni
 * se borra (ver migración). Toda la lógica de crear movimientos vive en
 * App\Services\DomainReserveFund para garantizar la invariante del saldo.
 */
class DomainReserveMovement extends Model
{
    protected $fillable = [
        'domain_id',
        'type',
        'amount_cents',
        'balance_after_cents',
        'reference',
        'note',
    ];

    protected $casts = [
        'amount_cents'        => 'integer',
        'balance_after_cents' => 'integer',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }
}
