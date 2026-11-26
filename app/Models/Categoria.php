<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $primaryKey = 'id_categoria';

    protected $fillable = [
        'nombre',
        'id_ambiente',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    /**
     * Una categoría pertenece a un ambiente.
     */
    public function ambiente()
    {
        return $this->belongsTo(Ambiente::class, 'id_ambiente', 'id_ambiente');
    }

    /**
     * Una categoría tiene muchos activos.
     */
    public function activos()
    {
        return $this->hasMany(ActivoFijo::class, 'id_categoria', 'id_categoria');
    }
}
