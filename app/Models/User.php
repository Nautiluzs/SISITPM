<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'ci', 'nombre_completo', 'email', 'password',
        'cargo', 'unidad', 'rol', 'estado', 'id_carrera',
        'puede_verificar',  // ⭐ NUEVO
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'estado' => 'string',
        'rol' => 'string',
        'puede_verificar' => 'boolean',  // ⭐ NUEVO
    ];

    public function setNombreCompletoAttribute(?string $nombre): void
    {
        $this->attributes['nombre_completo'] = $nombre === null
            ? null
            : mb_strtoupper(trim($nombre), 'UTF-8');
    }

    // Cache por instancia para evitar consultas repetidas en el mismo request
    protected ?array $_idsAmbientesVisiblesCache = null;

    protected ?array $_idsCarrerasVisiblesCache = null;

    // ============================================================
    // AUTENTICACIÓN
    // ============================================================
    public function getAuthPassword()
    {
        return $this->password;
    }

    public function historialReportes()
    {
        return $this->hasMany(HistorialReporte::class, 'id_usuario', 'id_usuario');
    }

    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    // ============================================================
    // ROLES
    // ============================================================

    public const ROL_ADMINISTRADOR = 'ADMINISTRADOR';

    public const ROL_INVENTARIADOR = 'INVENTARIADOR';

    public const ROL_AYUDANTE = 'AYUDANTE';

    public const ROL_JEFE_CARRERA = 'JEFE_CARRERA';

    public const ROL_CUSTODIO = 'DOCENTE_CUSTODIO';

    public const ROL_RECTOR = 'RECTOR';

    /**
     * Roles válidos. `CUSTODIO` y `VERIFICADOR` son valores heredados: se
     * aceptan al leer, pero no se ofrecen al crear usuarios.
     */
    public const ROLES = [
        self::ROL_ADMINISTRADOR => 'Administrador',
        self::ROL_INVENTARIADOR => 'Inventariador',
        self::ROL_AYUDANTE => 'Ayudante (temporal)',
        self::ROL_JEFE_CARRERA => 'Jefe de Carrera',
        self::ROL_CUSTODIO => 'Docente Custodio',
        self::ROL_RECTOR => 'Rector',
    ];

    public static function esRolValido(?string $rol): bool
    {
        return $rol !== null && array_key_exists($rol, self::ROLES);
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    public function esInventariador(): bool
    {
        return $this->rol === self::ROL_INVENTARIADOR;
    }

    public function esAyudante(): bool
    {
        return $this->rol === self::ROL_AYUDANTE;
    }

    public function esJefeCarrera(): bool
    {
        return $this->rol === self::ROL_JEFE_CARRERA;
    }

    public function esCustodio(): bool
    {
        return $this->rol === self::ROL_CUSTODIO;
    }

    public function esRector(): bool
    {
        return $this->rol === self::ROL_RECTOR;
    }

    /**
     * Solo la administradora puede crear, modificar y eliminar.
     */
    public function esOperativo(): bool
    {
        return $this->esInventariador() || $this->esAyudante();
    }

    /**
     * Custodio, jefe de carrera y rector solo revisan: no registran ni
     * modifican nada.
     */
    public function esSoloLectura(): bool
    {
        return $this->esCustodio() || $this->esJefeCarrera() || $this->esRector();
    }

    public function nombreRol(): string
    {
        return self::ROLES[$this->rol] ?? ($this->rol ?: 'Sin rol');
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'ACTIVO';
    }

    // ============================================================
    // PERMISOS
    // ============================================================

    /**
     * Alcance total: administradora, inventariador y ayudante recorren
     * todas las carreras y ambientes.
     */
    public function puedeVerTodo(): bool
    {
        return $this->esAdmin() || $this->esOperativo();
    }

    public function puedeVerTodasLasCarreras(): bool
    {
        return $this->puedeVerTodo();
    }

    /**
     * Todo usuario con sesión puede consultar el inventario; lo que cambia
     * es el alcance, que aplica `ActivoFijo::scopeParaUsuario`.
     */
    public function puedeVerInventario(): bool
    {
        return $this->rol !== null && $this->rol !== '';
    }

    /**
     * Solo la administradora modifica activos, carreras, ambientes, ítems
     * y usuarios.
     */
    public function puedeModificar(): bool
    {
        return $this->esAdmin();
    }

    public function puedeModificarActivos(): bool
    {
        return $this->esAdmin();
    }

    /**
     * Registra verificaciones físicas y las deja en estado PENDIENTE.
     * El flag `puede_verificar` deja que la administradora abra este
     * permiso a un rol de lectura, pero nunca a otro administrador.
     */
    public function puedeRegistrarVerificacion(): bool
    {
        return $this->esAdmin()
            || $this->esOperativo()
            || (bool) $this->puede_verificar;
    }

    /**
     * Alias usado por las vistas y el middleware.
     */
    public function puedeVerificar(): bool
    {
        return $this->puedeRegistrarVerificacion();
    }

    /**
     * Escanear QR es una consulta de lectura: todos pueden.
     */
    public function puedeEscanearQr(): bool
    {
        return $this->rol !== null && $this->rol !== '';
    }

    /**
     * Cierra una verificación de un ambiente y avisa a la administradora.
     */
    public function puedeFinalizarVerificacion(): bool
    {
        return $this->puedeRegistrarVerificacion();
    }

    public function puedeAprobar(): bool
    {
        return $this->esAdmin();
    }

    /**
     * Bajas, transferencias y sustituciones se solicitation; la
     * administradora es quien las aprueba.
     */
    public function puedeSolicitarBajaOTransferencia(): bool
    {
        return $this->puedeRegistrarVerificacion();
    }

    public function puedeGestionarUsuarios(): bool
    {
        return $this->esAdmin();
    }

    // ============================================================
    // ALCANCE DE LA INFORMACIÓN
    // ============================================================

    /**
     * Ambientes que el usuario puede ver.
     * - administradora, inventariador y ayudante: todos;
     * - jefe de carrera: los ambientes de su carrera;
     * - docente custodio: los ambientes donde custodia, completos;
     * - rector y demás: los ambientes donde tiene activos propios.
     */
    public function ambientesVisibles()
    {
        if ($this->puedeVerTodo()) {
            return Ambiente::query();
        }

        if ($this->esJefeCarrera()) {
            return Ambiente::query()->where('id_carrera', $this->id_carrera);
        }

        return Ambiente::query()->whereIn('id_ambiente', $this->idsAmbientesVisibles());
    }

    /**
     * Ids de ambientes visibles, o `null` cuando el alcance es total.
     */
    public function idsAmbientesVisibles(): ?array
    {
        if ($this->_idsAmbientesVisiblesCache !== null) {
            return $this->_idsAmbientesVisiblesCache;
        }

        if ($this->puedeVerTodo()) {
            $this->_idsAmbientesVisiblesCache = null;

            return null;
        }

        if ($this->esJefeCarrera()) {
            $result = Ambiente::query()
                ->where('id_carrera', $this->id_carrera)
                ->pluck('id_ambiente')
                ->all();
            $this->_idsAmbientesVisiblesCache = $result;

            return $result;
        }

        if ($this->esCustodio()) {
            $porBienes = $this->activosCustodiados()
                ->whereNotNull('id_ambiente')
                ->distinct()
                ->pluck('id_ambiente');

            $porItems = $this->itemsVigentes()
                ->whereNotNull('items.id_ambiente')
                ->pluck('items.id_ambiente');

            $porPivote = collect($this->ambientesDelPivote());

            $result = $porBienes->merge($porItems)->merge($porPivote)->unique()->values()->all();
            $this->_idsAmbientesVisiblesCache = $result;

            return $result;
        }

        $porBienes = $this->activosCustodiados()
            ->whereNotNull('id_ambiente')
            ->distinct()
            ->pluck('id_ambiente');

        $porItems = $this->itemsVigentes()
            ->whereNotNull('items.id_ambiente')
            ->pluck('items.id_ambiente');

        $porPivote = collect($this->ambientesDelPivote());

        $result = $porBienes->merge($porItems)->merge($porPivote)->unique()->values()->all();
        $this->_idsAmbientesVisiblesCache = $result;

        return $result;
    }

    /**
     * Ids de carreras visibles, o `null` cuando el alcance es total.
     */
    public function idsCarrerasVisibles(): ?array
    {
        if ($this->_idsCarrerasVisiblesCache !== null) {
            return $this->_idsCarrerasVisiblesCache;
        }

        if ($this->puedeVerTodasLasCarreras()) {
            $this->_idsCarrerasVisiblesCache = null;

            return null;
        }

        $porAmbientes = $this->ambientesVisibles()->pluck('id_carrera');

        if ($this->id_carrera) {
            $porAmbientes = $porAmbientes->push($this->id_carrera);
        }

        $result = $porAmbientes->unique()->values()->all();
        $this->_idsCarrerasVisiblesCache = $result;

        return $result;
    }

    /**
     * Aplica el alcance del usuario a una consulta de activos.
     */
    public function scopeActivos($query)
    {
        if ($this->esAdmin() || $this->esOperativo()) {
            return $query;
        }

        $idsAmbientes = $this->idsAmbientesVisibles();

        return $query->where(function ($q) use ($idsAmbientes) {
            $q->where('id_custodio', $this->id_usuario);

            if ($this->id_carrera) {
                $q->orWhereIn('id_item', function ($sub) {
                    $sub->select('id_item')
                        ->from('item_usuario')
                        ->where('id_usuario', $this->id_usuario)
                        ->whereNull('fecha_fin');
                });
            }

            if (! empty($idsAmbientes)) {
                $q->orWhereIn('id_ambiente', $idsAmbientes);
            }
        });
    }

    // ============================================================
    // RELACIONES
    // ============================================================
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'id_carrera', 'id_carrera');
    }

    public function activosCustodiados()
    {
        return $this->hasMany(ActivoFijo::class, 'id_custodio', 'id_usuario');
    }

    /**
     * Ítems que la persona tiene asignados. Un usuario puede tener varios,
     * y un mismo ítem puede tener 2, 3 o más custodios.
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_usuario', 'id_usuario', 'id_item')
            ->withPivot(['tipo', 'fecha_inicio', 'fecha_fin'])
            ->withTimestamps();
    }

    /**
     * Ítems que la persona tiene asignados y aún no devolvió.
     * La consulta parte de `items`, así que el estado del usuario se
     * comprueba sobre el modelo y no con una columna de la BD.
     */
    public function itemsVigentes()
    {
        if ($this->estado !== 'ACTIVO') {
            return $this->items()->whereRaw('1 = 0');
        }

        return $this->items()->whereNull('item_usuario.fecha_fin');
    }

    /**
     * Ambientes donde la persona tiene bienes en custodia. Al final de la
     * gestión firma la liberación de esos ambientes.
     */
    public function ambientesCustodiados()
    {
        $porBienes = $this->activosCustodiados()
            ->whereNotNull('id_ambiente')
            ->distinct()
            ->pluck('id_ambiente');

        // También cuenta como custodio quien tiene un ítem del ambiente,
        // aunque todavía no tenga bienes a su nombre.
        $porItems = $this->itemsVigentes()
            ->whereNotNull('items.id_ambiente')
            ->pluck('items.id_ambiente');

        return Ambiente::whereIn('id_ambiente', $porBienes->merge($porItems)->unique());
    }

    public function custodiaAmbiente($idAmbiente): bool
    {
        if ($this->activosCustodiados()->where('id_ambiente', $idAmbiente)->exists()) {
            return true;
        }

        return $this->itemsVigentes()
            ->where('items.id_ambiente', $idAmbiente)
            ->exists();
    }

    /**
     * Ambientes donde el usuario está registrado como custodio directo
     * en la tabla pivote ambiente_user (aunque no tenga bienes propios).
     */
    private function ambientesDelPivote(): array
    {
        return DB::table('ambiente_user')
            ->where('id_usuario', $this->id_usuario)
            ->pluck('id_ambiente')
            ->all();
    }
}
