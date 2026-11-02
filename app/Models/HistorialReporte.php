<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialReporte extends Model
{
    protected $table = 'historial_reportes';

    protected $fillable = [
        'id_usuario',
        'tipo_reporte',
        'formato',
        'nombre_archivo',
        'filtros',
    ];

    protected $casts = [
        'filtros' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
