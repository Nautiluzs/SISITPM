<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\HistorialTitular;
use App\Models\Item;
use App\Models\User;
use App\Models\VerificacionFisica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403, 'Acceso denegado');

        $ordenNombre = $request->input('orden_nombre', 'asc');
        if (! in_array($ordenNombre, ['asc', 'desc'], true)) {
            $ordenNombre = 'asc';
        }

        $query = User::with(['carrera', 'items']);

        // Filtro por rol
        if ($request->filled('rol') && $request->rol !== 'Todos') {
            $query->where('rol', $request->rol);
        }

        // ⭐ Búsqueda estricta por nombre, CI, email o número de ítem
        if ($request->filled('buscar')) {
            $s = trim($request->buscar);

            $query->where(function ($q) use ($s) {
                $q->where('nombre_completo', 'like', "%{$s}%")
                    ->orWhere('ci', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhereHas('items', function ($iq) use ($s) {
                        $iq->where('numero_item', 'like', "%{$s}%");
                    });
            });
        }

        return view('usuarios.index', [
            'usuarios' => $query
                ->orderBy('nombre_completo', $ordenNombre)
                ->get(),
            'carreras' => Carrera::orderBy('nombre')->get(),
            'ordenNombre' => $ordenNombre,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ci' => 'required|string|max:20|unique:usuarios,ci',
            'nombre_completo' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:usuarios,email',
            'rol' => ['required', 'string', Rule::in(array_keys(User::ROLES))],
            'cargo' => 'required|string|max:100',
            'unidad' => 'required|string|max:100',
            'carrera_id' => 'nullable|exists:carreras,id_carrera',
            'puede_verificar' => 'nullable|boolean',
            'items' => 'nullable|array',
            'items.*' => 'nullable|string|max:20',
        ], [
            'rol.in' => 'El rol seleccionado no es válido.',
        ]);

        // Generar contraseña temporal
        $partes = explode(' ', trim($request->nombre_completo));
        $primerNombre = $partes[0] ?? 'usu';
        $apellido = $partes[1] ?? 'sis';
        $base = strtolower(substr($primerNombre, 0, 3).substr($apellido, 0, 3));
        $passwordGenerada = $base.rand(100, 999);

        $usuario = User::create([
            'ci' => $request->ci,
            'email' => $request->email,
            'nombre_completo' => $request->nombre_completo,
            'cargo' => $request->cargo,
            'unidad' => $request->unidad,
            'password' => Hash::make($passwordGenerada),
            'rol' => $data['rol'],
            'id_carrera' => $request->carrera_id,
            'estado' => 'ACTIVO',
            'puede_verificar' => $request->boolean('puede_verificar'),
        ]);

        $this->sincronizarItems($usuario, $request->input('items', []));

        // Enviar email
        try {
            if (! empty($usuario->email)) {
                Mail::raw("Hola {$usuario->nombre_completo},\n\nTu contraseña temporal: {$passwordGenerada}", function ($m) use ($usuario) {
                    $m->to($usuario->email)->subject('Credenciales - SISActivos');
                });
            }
        } catch (\Exception $e) {
            // Silencioso
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario registrado.');
    }

    public function update(Request $request, $id)
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        $usuario = User::where('id_usuario', $id)->firstOrFail();

        $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'ci' => ['required', 'string', 'max:20', Rule::unique('usuarios', 'ci')->ignore($usuario->id_usuario, 'id_usuario')],
            'email' => ['required', 'email', Rule::unique('usuarios', 'email')->ignore($usuario->id_usuario, 'id_usuario')],
            'cargo' => 'required|string',
            'unidad' => 'required|string',
            'rol' => ['required', 'string', Rule::in(array_keys(User::ROLES))],
            'carrera_id' => 'nullable|exists:carreras,id_carrera',
            'puede_verificar' => 'nullable|boolean',
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
            'items' => 'nullable|array',
            'items.*' => 'nullable|string|max:20',
            'cambio_titular' => 'nullable|boolean',
            'motivo_cambio' => 'nullable|string|max:255',
        ], [
            'rol.in' => 'El rol seleccionado no es válido.',
            'estado.in' => 'El estado debe ser ACTIVO o INACTIVO.',
        ]);

        $estadoAnterior = $usuario->estado;
        $rolAnterior = $usuario->rol;
        $nombreAnterior = $usuario->nombre_completo;

        // La administradora no puede quedar sin sesión ni quitarse el rol:
        // siempre tiene que existir una administración activa.
        if ($estadoAnterior === 'ACTIVO' && $request->estado === 'INACTIVO') {
            if ((int) $usuario->id_usuario === (int) Auth::id()) {
                return back()->withInput()->with('error', 'No puedes desactivar tu propia cuenta.');
            }

            if ($usuario->esAdmin() && User::where('rol', User::ROL_ADMINISTRADOR)->where('estado', 'ACTIVO')->count() <= 1) {
                return back()->withInput()->with('error', 'Debe quedar al menos una administradora activa.');
            }
        }

        // Desactivar a alguien o cambiarle el rol cierra sus ítems
        // vigentes: deja de ser custodio, pero el historial se conserva.
        $suspender = $estadoAnterior === 'ACTIVO'
            && ($request->estado === 'INACTIVO' || $request->rol !== $rolAnterior);

        $usuario->update([
            'ci' => $request->ci,
            'email' => $request->email,
            'nombre_completo' => $request->nombre_completo,
            'cargo' => $request->cargo,
            'unidad' => $request->unidad,
            'rol' => $request->rol,
            'id_carrera' => $request->carrera_id,
            'puede_verificar' => $request->boolean('puede_verificar'),
            'estado' => $request->estado,
        ]);

        if ($usuario->estaActivo()) {
            $this->sincronizarItems($usuario, $request->input('items', []));
        }

        if ($suspender) {
            $this->cerrarItemsVigentes($usuario);
        }

        // Si cambió el nombre, el ítem y sus activos se mantienen: solo se
        // asienta en el historial quién estaba antes y quién está ahora.
        if ($nombreAnterior !== $usuario->nombre_completo) {
            $this->registrarCambioTitular(
                $usuario,
                $nombreAnterior,
                $request->boolean('cambio_titular') ? $request->input('motivo_cambio') : null
            );
        }

        $mensaje = 'Usuario actualizado.';

        if ($suspender) {
            $mensaje .= $usuario->estaActivo()
                ? ' Su rol cambió, por lo que dejó de custodiar sus ítems.'
                : ' Quedó desactivado y ya no puede iniciar sesión.';
        }

        return redirect()->route('usuarios.index')->with('success', $mensaje);
    }

    // ⭐ NUEVO: Alternar permiso de verificación rápido (desde la lista)
    public function toggleVerificar($id)
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        $usuario = User::where('id_usuario', $id)->firstOrFail();

        // No permitir quitar el permiso al admin
        if ($usuario->esAdmin()) {
            return back()->with('error', 'El administrador siempre puede verificar.');
        }

        $usuario->update([
            'puede_verificar' => ! $usuario->puede_verificar,
        ]);

        $estado = $usuario->puede_verificar ? 'habilitado' : 'deshabilitado';

        return back()->with('success', "Permiso de verificación {$estado} para {$usuario->nombre_completo}.");
    }

    public function destroy($id)
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        $usuario = User::where('id_usuario', $id)->firstOrFail();

        if ($usuario->id_usuario === Auth::id()) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        $activos = $usuario->activosCustodiados()->count();

        if ($activos > 0) {
            return back()->with('error', "No se puede eliminar a {$usuario->nombre_completo} porque tiene {$activos} activo(s) a su nombre. Desvincula primero los activos o desvinculá su custodia de los ítems.");
        }

        $verificaciones = VerificacionFisica::where('id_verificador', $usuario->id_usuario)->count();
        if ($verificaciones > 0) {
            return back()->with('error', "No se puede eliminar a {$usuario->nombre_completo} porque tiene {$verificaciones} verificación(es) registradas. Desactiva el usuario para conservar el historial.");
        }

        $usuario->items()->detach();
        $usuario->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    public function generarQr($id)
    {
        $usuario = User::where('id_usuario', $id)->firstOrFail();

        $contenidoQr = "SISITPM - CREDENCIAL DE ACCESO\n".
                       "C.I.: {$usuario->ci}\n".
                       "Nombre: {$usuario->nombre_completo}\n".
                       'Item: '.($usuario->items->pluck('numero_item')->implode(', ') ?: 'N/A')."\n".
                       "Rol: {$usuario->rol}\n".
                       'Cargo: '.($usuario->cargo ?? 'N/A')."\n".
                       'Unidad: '.($usuario->unidad ?? 'N/A')."\n".
                       'Correo: '.($usuario->email ?? 'N/A');

        $qrImage = QrCode::size(220)->generate($contenidoQr);

        return view('usuarios.qr', compact('usuario', 'qrImage'));
    }

    public function enviarCredenciales($id)
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        $usuario = User::where('id_usuario', $id)->firstOrFail();

        if (empty($usuario->email)) {
            return back()->with('error', 'El usuario no tiene email.');
        }

        $passwordTemporal = 'Sis'.rand(1000, 9999).'*';

        try {
            Mail::raw("Hola {$usuario->nombre_completo},\n\nNueva contraseña: {$passwordTemporal}", function ($m) use ($usuario) {
                $m->to($usuario->email)->subject('Nueva Contraseña - SISITPM');
            });
            $usuario->update(['password' => Hash::make($passwordTemporal)]);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo enviar el correo. Verifica la configuración SMTP.');
        }

        return back()->with('success', "Contraseña enviada a {$usuario->email}.");
    }

    public function reenviarCredencialesSeleccionados(Request $request)
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        $ids = $request->validate([
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['integer', 'exists:usuarios,id_usuario'],
        ])['usuarios'];

        return $this->reenviarCredencialesParaUsuarios($ids);
    }

    public function reenviarCredencialesTodos()
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        return $this->reenviarCredencialesParaUsuarios(
            User::query()->whereNotNull('email')->pluck('id_usuario')->all()
        );
    }

    public function destroySeleccionados(Request $request)
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        $ids = $request->validate([
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['integer', 'exists:usuarios,id_usuario'],
        ])['usuarios'];

        $eliminados = 0;
        $omitidos = [];

        foreach (User::whereIn('id_usuario', $ids)->get() as $usuario) {
            if ((int) $usuario->id_usuario === (int) Auth::id()) {
                $omitidos[] = "{$usuario->nombre_completo}: es tu usuario actual.";

                continue;
            }

            $activos = $usuario->activosCustodiados()->count();
            if ($activos > 0) {
                $omitidos[] = "{$usuario->nombre_completo}: tiene {$activos} activo(s) asignado(s).";

                continue;
            }

            $verificaciones = VerificacionFisica::where('id_verificador', $usuario->id_usuario)->count();
            if ($verificaciones > 0) {
                $omitidos[] = "{$usuario->nombre_completo}: tiene {$verificaciones} verificación(es) registradas.";

                continue;
            }

            $usuario->items()->detach();
            $usuario->delete();
            $eliminados++;
        }

        $mensaje = "{$eliminados} usuario(s) eliminado(s).";
        if ($omitidos !== []) {
            $mensaje .= ' Omitidos: '.implode(' ', $omitidos);
        }

        return back()->with($eliminados > 0 ? 'success' : 'error', $mensaje);
    }

    private function reenviarCredencialesParaUsuarios(array $ids)
    {
        $enviados = 0;
        $omitidos = [];

        foreach (User::whereIn('id_usuario', $ids)->get() as $usuario) {
            if (empty($usuario->email)) {
                $omitidos[] = "{$usuario->nombre_completo}: no tiene correo.";

                continue;
            }

            $passwordTemporal = 'Sis'.rand(1000, 9999).'*';

            try {
                Mail::raw("Hola {$usuario->nombre_completo},\n\nNueva contraseña: {$passwordTemporal}", function ($m) use ($usuario) {
                    $m->to($usuario->email)->subject('Nueva Contraseña - SISITPM');
                });
                $usuario->update(['password' => Hash::make($passwordTemporal)]);
                $enviados++;
            } catch (\Throwable $e) {
                report($e);
                $omitidos[] = "{$usuario->nombre_completo}: no se pudo enviar el correo.";
            }
        }

        $mensaje = "{$enviados} contraseña(s) enviada(s).";
        if ($omitidos !== []) {
            $mensaje .= ' Omitidos: '.implode(' ', $omitidos);
        }

        return back()->with($enviados > 0 ? 'success' : 'error', $mensaje);
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        $carreras = Carrera::orderBy('nombre')->get();
        $items = Item::orderBy('numero_item')->get();

        return view('usuarios.create', compact('carreras', 'items'));
    }

    /**
     * Mostrar formulario de edición.
     */
    public function edit($id)
    {
        abort_if(Auth::user()->rol !== 'ADMINISTRADOR', 403);

        $usuario = User::where('id_usuario', $id)->firstOrFail();
        $carreras = Carrera::orderBy('nombre')->get();
        $items = Item::orderBy('numero_item')->get();

        $itemsAsignados = $usuario->items()->pluck('numero_item')->all();

        return view('usuarios.edit', compact('usuario', 'carreras', 'items', 'itemsAsignados'));
    }

    /**
     * Buscador de personas por nombre, C.I. o número de ítem.
     * Devuelve JSON para el componente x-person-picker.
     */
    public function buscar(Request $request)
    {
        $termino = trim((string) $request->input('q'));

        $query = User::query()
            ->with(['itemsVigentes', 'carrera', 'activosCustodiados'])
            ->where(function ($q) use ($termino) {
                $q->where('estado', 'ACTIVO');

                if ($termino !== '' && preg_match('/^[0-9]{1,10}$/', $termino)) {
                    $q->orWhereHas('items', function ($itemQuery) use ($termino) {
                        $itemQuery->where('numero_item', $termino);
                    });
                }
            });

        // Filtros: carrera, unidad organizacional y rol
        if ($request->filled('carrera_id')) {
            $query->where('id_carrera', $request->carrera_id);
        }

        if ($request->filled('unidad')) {
            $query->where('unidad', $request->unidad);
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }

        if ($request->filled('ambiente_id')) {
            $ambienteId = (int) $request->input('ambiente_id');
            $query->orderByRaw(
                'CASE WHEN EXISTS (SELECT 1 FROM activos_fijos WHERE activos_fijos.id_custodio = usuarios.id_usuario AND activos_fijos.id_ambiente = ? AND activos_fijos.deleted_at IS NULL)
                    OR EXISTS (SELECT 1 FROM item_usuario
                        INNER JOIN items ON items.id_item = item_usuario.id_item
                        WHERE item_usuario.id_usuario = usuarios.id_usuario
                        AND item_usuario.fecha_fin IS NULL
                        AND items.id_ambiente = ?
                        AND items.deleted_at IS NULL)
                    THEN 0 ELSE 1 END',
                [$ambienteId, $ambienteId]
            );
        }

        if ($termino !== '') {
            $query->where(function ($q) use ($termino) {
                $q->where('nombre_completo', 'like', "%{$termino}%")
                    ->orWhere('ci', 'like', "%{$termino}%")
                    ->orWhere('cargo', 'like', "%{$termino}%")
                    ->orWhere('unidad', 'like', "%{$termino}%")
                    ->orWhereHas('items', function ($iq) use ($termino) {
                        $iq->where('numero_item', 'like', "%{$termino}%");
                    });
            });
        }

        $usuarios = $query->orderBy('nombre_completo')->limit(60)->get();

        $resultados = $usuarios->map(fn ($u) => [
            'id_usuario' => $u->id_usuario,
            'nombre_completo' => $u->nombre_completo,
            'ci' => $u->ci,
            'cargo' => $u->cargo,
            'unidad' => $u->unidad,
            'carrera' => $u->carrera?->nombre,
            'rol' => $u->rol,
            'items' => $u->itemsVigentes->pluck('numero_item')->all(),
            'asignado_en_ambiente' => $request->filled('ambiente_id')
                && (
                    $u->activosCustodiados->contains('id_ambiente', (int) $request->input('ambiente_id'))
                    || $u->itemsVigentes->contains('id_ambiente', (int) $request->input('ambiente_id'))
                ),
            'texto' => $u->nombre_completo
                .' — '.($u->cargo ?: 'Sin cargo')
                .($u->itemsVigentes->isNotEmpty() ? ' [ítem '.$u->itemsVigentes->pluck('numero_item')->implode(', ').']' : ''),
        ]);

        // Si buscó un número de ítem y no apareció su titular, avisar por qué.
        $aviso = null;

        if ($resultados->isEmpty() && preg_match('/^[0-9]{1,10}$/', $termino)) {
            $item = Item::where('numero_item', $termino)->first();

            $aviso = $item
                ? 'El ítem '.$termino.' existe pero está vacante: no tiene titular asignado.'
                : 'El ítem '.$termino.' no está registrado en el sistema.';
        }

        return response()->json([
            'resultados' => $resultados,
            'aviso' => $aviso,
            'filtros' => $this->filtrosPersonas(),
        ]);
    }

    /**
     * Opciones de los filtros del buscador de personas.
     */
    private function filtrosPersonas(): array
    {
        return [
            'carreras' => Carrera::orderBy('nombre')->get(['id_carrera', 'nombre'])
                ->map(fn ($c) => ['valor' => (string) $c->id_carrera, 'texto' => $c->nombre])->all(),
            'unidades' => User::where('estado', 'ACTIVO')->whereNotNull('unidad')
                ->distinct()->orderBy('unidad')->pluck('unidad')
                ->map(fn ($u) => ['valor' => $u, 'texto' => $u])->all(),
            'roles' => [
                ['valor' => 'ADMINISTRADOR', 'texto' => 'Administrador'],
                ['valor' => 'INVENTARIADOR', 'texto' => 'Inventariador'],
                ['valor' => 'AYUDANTE', 'texto' => 'Ayudante'],
                ['valor' => 'DOCENTE_CUSTODIO', 'texto' => 'Docente Custodio'],
                ['valor' => 'JEFE_CARRERA', 'texto' => 'Jefe de Carrera'],
                ['valor' => 'RECTOR', 'texto' => 'Rector'],
            ],
        ];
    }

    /**
     * Sincroniza los ítems de una persona. El ítem es una entidad
     * permanente: si el número no existe, se crea. Una persona puede
     * tener varios, y un mismo ítem puede tener 2, 3 o más custodios.
     */
    private function sincronizarItems(User $usuario, array $numeros): void
    {
        $numeros = array_values(array_filter(array_unique(array_map('trim', $numeros))));

        $idsItem = [];

        foreach ($numeros as $numero) {
            $item = Item::firstOrCreate(
                ['numero_item' => $numero],
                [
                    'descripcion' => null,
                    'id_carrera' => $usuario->id_carrera,
                    'unidad' => $usuario->unidad,
                    'estado' => 'VACANTE',
                ]
            );

            $idsItem[$item->id_item] = [
                'tipo' => 'TITULAR',
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin' => null,
            ];
        }

        $previos = $usuario->items()->pluck('items.id_item')->all();
        $usuario->items()->sync($idsItem);

        foreach (Item::whereIn('id_item', array_unique(array_merge($previos, array_keys($idsItem))))->get() as $item) {
            $item->recalcularEstado();
        }
    }

    /**
     * Cierra la custodia de los ítems asignados. El ítem no se borra ni
     * cambia de ambiente: simplemente deja de tener titular vigente, que
     * es lo que revisan `User::itemsVigentes()` y la liberación.
     */
    private function cerrarItemsVigentes(User $usuario): void
    {
        $idsItem = $usuario->items()
            ->whereNull('item_usuario.fecha_fin')
            ->pluck('items.id_item');

        if ($idsItem->isEmpty()) {
            return;
        }

        $hoy = now()->toDateString();

        $usuario->items()->wherePivotNull('fecha_fin')->update([
            'fecha_fin' => $hoy,
            'updated_at' => now(),
        ]);

        Item::whereIn('id_item', $idsItem)->get()->each->recalcularEstado();
    }

    /**
     * Cuando alguien renuncia el registro de usuario no se borra: solo
     * cambian los datos personales. Este asiento deja asentado quién
     * estaba antes y quién está ahora, para la auditoría.
     */
    private function registrarCambioTitular(User $usuario, string $nombreAnterior, ?string $motivo): void
    {
        foreach ($usuario->items as $item) {
            HistorialTitular::create([
                'id_item' => $item->id_item,
                'id_usuario_anterior' => $usuario->id_usuario,
                'id_usuario_nuevo' => $usuario->id_usuario,
                'nombre_anterior' => $nombreAnterior,
                'nombre_nuevo' => $usuario->nombre_completo,
                'tipo' => 'SUSTITUCION',
                'fecha_evento' => now()->toDateString(),
                'motivo' => $motivo ?: 'Cambio de titular: renuncia o sucesión de cargo. El ítem y sus activos se mantienen.',
                'id_usuario_registro' => Auth::id(),
            ]);
        }
    }
}
