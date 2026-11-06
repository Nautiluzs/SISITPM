<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ActivoFijo extends Model
{
    use HasFactory;

    protected $table = 'activos_fijos';

    protected $primaryKey = 'id_activo';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'codigo_activo', 'codigo_antiguo', 'tipo_bien', 'rv', 'nombre', 'descripcion', 'observaciones',
        'marca', 'modelo', 'numero_serie',
        'fecha_adquisicion', 'valor_adquisicion',
        'id_categoria', 'id_ambiente', 'id_fuente', 'id_custodio', 'id_item',
        'estado_fisico', 'estado_registro',
        'ruta_qr', 'imagen', 'imagen_lateral', 'imagen_detalle', 'codigo_qr',  // ⭐ NUEVAS
    ];

    protected $casts = [
        'fecha_adquisicion' => 'date',
        'valor_adquisicion' => 'decimal:2',
    ];

    // ==========================================
    // VALIDACIÓN
    // ==========================================
    public static function rules($id = null, string $tipoBien = 'ACTIVO_FIJO'): array
    {
        // Un no activo (escoba, papeleras, engrampadora) no lleva marca,
        // modelo ni número de serie: se compra y se reemplaza.
        $exigeSerie = $tipoBien === 'ACTIVO_FIJO';

        return [
            // Identificación
            'codigo_activo' => [
                'required', 'string', 'min:3', 'max:50',
                Rule::unique('activos_fijos', 'codigo_activo')->ignore($id, 'id_activo'),
            ],
            'codigo_antiguo' => 'nullable|string|max:50',
            'tipo_bien' => ['required', Rule::in(['ACTIVO_FIJO', 'NO_ACTIVO'])],
            'nombre' => 'required|string|max:150',
            'cantidad' => 'nullable|integer|min:1|max:100',

            // Descripción
            'descripcion' => 'required|string',

            // Especificaciones
            'marca' => ($exigeSerie ? 'required' : 'nullable').'|string|max:80',
            'modelo' => ($exigeSerie ? 'required' : 'nullable').'|string|max:80',
            'numero_serie' => [
                $exigeSerie ? 'required' : 'nullable', 'string', 'max:80',
                Rule::unique('activos_fijos', 'numero_serie')->ignore($id, 'id_activo'),
            ],

            // Valores
            'fecha_adquisicion' => 'nullable|date',
            'valor_adquisicion' => 'nullable|numeric|min:0',

            // Clasificación
            'id_categoria' => 'required|exists:categorias,id_categoria',
            'id_ambiente' => 'required|exists:ambientes,id_ambiente',
            'id_fuente' => 'nullable|exists:fuentes_financiamiento,id_fuente',
            'id_custodio' => 'required|exists:usuarios,id_usuario',
            'id_item' => 'nullable|exists:items,id_item',

            // Estados
            'estado_fisico' => ['nullable', Rule::in(['B', 'R', 'M', 'FF'])],
            'estado_registro' => ['nullable', Rule::in(['ACTIVO', 'ASIGNADO', 'EN_TRANSFERENCIA', 'BAJA'])],

            // Archivos
            // Imágenes (la principal es obligatoria, las otras opcionales)
            'imagen' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'imagen_lateral' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'imagen_detalle' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }

    public static array $messages = [
        // Identificación
        'codigo_activo.required' => 'El código del activo es obligatorio.',
        'codigo_activo.min' => 'El código debe tener al menos 3 caracteres.',
        'codigo_activo.max' => 'El código no debe superar los 50 caracteres.',
        'codigo_activo.unique' => 'Este código ya está registrado en otro activo.',
        'codigo_antiguo.max' => 'El código antiguo no debe superar los 50 caracteres.',
        'tipo_bien.required' => 'Debe indicar si es activo fijo o no activo.',
        'tipo_bien.in' => 'El tipo de bien no es válido.',
        'nombre.required' => 'El nombre del activo es obligatorio.',
        'nombre.max' => 'El nombre no debe superar los 150 caracteres.',
        'cantidad.integer' => 'La cantidad debe ser un número entero.',
        'cantidad.min' => 'La cantidad debe ser al menos 1.',
        'cantidad.max' => 'La cantidad no puede ser mayor a 100.',

        // Especificaciones
        'marca.max' => 'La marca no debe superar los 80 caracteres.',
        'modelo.max' => 'El modelo no debe superar los 80 caracteres.',
        'numero_serie.unique' => 'Este número de serie ya está registrado.',

        // Valores
        'fecha_adquisicion.date' => 'La fecha de adquisición no es válida.',
        'valor_adquisicion.numeric' => 'El valor debe ser un número.',
        'valor_adquisicion.min' => 'El valor no puede ser negativo.',

        // Clasificación
        'id_categoria.required' => 'La categoría es obligatoria.',
        'id_categoria.exists' => 'La categoría seleccionada no existe.',
        'id_ambiente.required' => 'El ambiente es obligatorio.',
        'id_ambiente.exists' => 'El ambiente seleccionado no existe.',
        'id_fuente.exists' => 'La fuente de financiamiento no existe.',
        'id_custodio.required' => 'Debe asignar un custodio: el activo pertenece a una persona.',
        'id_custodio.exists' => 'El custodio seleccionado no existe.',
        'id_item.exists' => 'El ítem seleccionado no existe.',

        // Estados
        'estado_fisico.in' => 'El estado físico no es válido.',
        'estado_registro.in' => 'El estado de registro no es válido.',

        // Archivos
        'imagen.image' => 'El archivo debe ser una imagen.',
        'imagen.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
        'imagen.max' => 'La imagen no debe pesar más de 5 MB.',
        'imagen.required' => 'La foto frontal del activo es obligatoria.',
        'imagen_lateral.image' => 'La foto lateral debe ser una imagen.',
        'imagen_lateral.mimes' => 'La foto lateral debe ser JPG, PNG o WEBP.',
        'imagen_lateral.max' => 'La foto lateral no debe pesar más de 5 MB.',
        'imagen_detalle.image' => 'La foto de detalle debe ser una imagen.',
        'imagen_detalle.mimes' => 'La foto de detalle debe ser JPG, PNG o WEBP.',
        'imagen_detalle.max' => 'La foto de detalle no debe pesar más de 5 MB.',

    ];

    // ==========================================
    // RELACIONES
    // ==========================================
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function ambiente()
    {
        return $this->belongsTo(Ambiente::class, 'id_ambiente', 'id_ambiente');
    }

    public function fuente()
    {
        return $this->belongsTo(FuenteFinanciamiento::class, 'id_fuente', 'id_fuente');
    }

    public function fuenteFinanciamiento()
    {
        return $this->fuente();
    }

    public function custodio()
    {
        return $this->belongsTo(User::class, 'id_custodio', 'id_usuario');
    }

    public function custodioActual()
    {
        return $this->custodio();
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item', 'id_item');
    }

    public function verificaciones()
    {
        return $this->hasMany(VerificacionFisica::class, 'id_activo', 'id_activo');
    }

    // ==========================================
    // SCOPES
    // ==========================================
    public function scopeParaUsuario($query, $user)
    {
        if (! $user) {
            return $query;
        }

        return $user->scopeActivos($query);
    }

    /**
     * Solo la administradora, el inventariador y el ayudante recorren
     * todos los ambientes; los demás ven su propio alcance.
     */
    public function scopeVisibles($query, $user)
    {
        return $query->paraUsuario($user);
    }

    // ==========================================
    public function scopeTipoBien($query, $tipo)
    {
        if (! in_array($tipo, ['ACTIVO_FIJO', 'NO_ACTIVO'], true)) {
            return $query;
        }

        return $query->where('tipo_bien', $tipo);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================
    public function getTipoBienTextoAttribute(): string
    {
        return $this->tipo_bien === 'NO_ACTIVO' ? 'No activo' : 'Activo fijo';
    }

    public function esActivoFijo(): bool
    {
        return $this->tipo_bien !== 'NO_ACTIVO';
    }

    public function esNoActivo(): bool
    {
        return $this->tipo_bien === 'NO_ACTIVO';
    }

    public function getEstadoFisicoTextoAttribute(): string
    {
        return match ($this->estado_fisico) {
            'B' => 'BUENO',
            'R' => 'REGULAR',
            'M' => 'MALO',
            'FF' => 'FUERA DE FUNCIONAMIENTO',
            default => 'DESCONOCIDO',
        };
    }

    public function getGestionAttribute(): string
    {
        return $this->fecha_adquisicion
            ? $this->fecha_adquisicion->format('Y')
            : '—';
    }

    public function getEstadoRegistroTextoAttribute(): string
    {
        return match ($this->estado_registro) {
            'ACTIVO' => 'Activo',
            'ASIGNADO' => 'Asignado',
            'EN_TRANSFERENCIA' => 'En transferencia',
            'BAJA' => 'Baja',
            default => 'Desconocido',
        };
    }
}
