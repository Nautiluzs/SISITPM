@extends('layouts.app')

@section('content')
@php use Illuminate\Support\Str; @endphp

    @if (session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-semibold text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200" role="status">
            <svg class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 font-semibold text-red-800 shadow-sm dark:border-red-800 dark:bg-red-950/60 dark:text-red-200" role="alert">
            <span aria-hidden="true">⚠️</span>
            {{ session('error') }}
        </div>
    @endif

    <section class="hero-card" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 30px; border-radius: 16px; color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);">
        <div>
            <div style="display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,0.18);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Carrera</div>
            <h1 style="margin:10px 0 8px;font-size:28px;">{{ $nombre }}</h1>
            <p style="margin: 0; opacity: 0.9;">Jefe de Carrera: <strong>{{ $carrera->jefeCarrera?->nombre_completo ?? 'No asignado' }}</strong></p>
        </div>
        <div class="stat-grid" style="display: flex; gap: 10px;">
            <div style="background: rgba(15, 23, 42, 0.35); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 18px; border-radius: 12px; text-align: center; min-width: 90px;">
                <div style="font-size: 20px; font-weight: 800; color: white;">{{ $ambientes->count() }}</div>
                <span style="font-size: 10px; font-weight: 700; opacity: 0.8; text-transform: uppercase;">Ambientes</span>
            </div>
            <div style="background: rgba(15, 23, 42, 0.35); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 18px; border-radius: 12px; text-align: center; min-width: 90px;">
                <div style="font-size: 20px; font-weight: 800; color: white;">{{ $ambientes->sum(fn($amb) => $amb->activos_count ?? 0) }}</div>
                <span style="font-size: 10px; font-weight: 700; opacity: 0.8; text-transform: uppercase;">Activos</span>
            </div>
        </div>
    </section>

    <section class="panel" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-bottom:18px;">
            <div>
                <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">Ambientes relacionados</h2>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Selecciona el botón Ingresar del ambiente que deseas abrir.</p>
            </div>

            @if (auth()->check() && auth()->user()->rol === 'ADMINISTRADOR')
                <button type="button" onclick="openAmbienteModal()" style="background: #2563eb; color: white; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(37,99,235,0.2);">+ Nuevo ambiente</button>
            @endif
        </div>

        @if ($ambientes->count())
            <style>
                .folders-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                    gap: 16px;
                    margin-top: 15px;
                }
                .folder-card {
                    background: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 14px;
                    padding: 18px;
                    text-align: left;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    position: relative;
                }
                .folder-card:hover {
                    transform: translateY(-3px);
                    border-color: #2563eb;
                    box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.1);
                }
            </style>

            <div class="folders-grid">
    @foreach ($ambientes as $amb)
        <div class="folder-card" style="position:relative;">
            <span style="position:absolute;top:12px;left:12px;color:#000;font-size:18px;font-weight:900;z-index:2;">
                {{ $amb->id_ambiente }}
            </span>

            {{-- ⭐ BOTÓN ARRINCONADO ARRIBA A LA DERECHA --}}
            @if (auth()->check() && auth()->user()->rol === 'ADMINISTRADOR')
                <button type="button" onclick="event.stopPropagation(); openAsignarCustodiosModal('{{ $amb->id_ambiente }}', '{{ addslashes($amb->nombre) }}', @json(collect($amb->custodios)->pluck('id_usuario')))"
                    style="position: absolute; top: 12px; right: 12px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 11px; cursor: pointer; display: flex; align-items: center; gap: 3px; z-index: 5;">
                    👥 Asignar
                </button>
            @endif

            <div>
                {{-- ⭐ Acolchado a la derecha (padding-right: 75px) para que el botón no tape el código --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-right: 75px; padding-top: 24px;">
                    <span style="font-size: 28px;">📂</span>
                    <span style="font-size: 10px; font-weight: 800; background: #eff6ff; color: #1d4ed8; padding: 2px 6px; border-radius: 4px;">
                        {{ $amb->codigo ?? 'S/C' }}
                    </span>
                </div>

                <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">{{ $amb->nombre }}</h3>
                <p style="font-size: 11px; color: #64748b; margin: 0 0 10px 0;">{{ $amb->bloque ? 'Bloque ' . $amb->bloque : 'Bloque N/A' }} · {{ $amb->piso ? 'Piso ' . $amb->piso : 'Piso N/D' }}</p>

                {{-- Múltiples Docentes Custodios --}}
                <div style="font-size: 10px; background: #f8fafc; padding: 8px; border-radius: 8px; border: 1px solid #f1f5f9; margin-bottom: 12px;">
                    <span style="font-weight: 700; color: #334155; display: block; margin-bottom: 3px;">👥 Docentes Custodios:</span>
                    @forelse($amb->custodios ?? [] as $custodio)
                        <div style="color: #475569;">• {{ $custodio->nombre_completo }}</div>
                    @empty
                        <span style="color: #94a3b8; font-style: italic;">Sin custodios asignados</span>
                    @endforelse
                </div>
            </div>

            {{-- Botones de edición y eliminación --}}
            <div style="display: flex; gap: 6px; justify-content: flex-end; border-top: 1px solid #f1f5f9; padding-top: 10px;" onclick="event.stopPropagation();">
                <a href="{{ route('ambientes.show', $amb->id_ambiente) }}"
                    style="background:#2563eb;color:#fff;padding:7px 12px;border-radius:8px;font-weight:700;text-decoration:none;font-size:12px;">
                    Ingresar
                </a>
                @if (auth()->check() && auth()->user()->rol === 'ADMINISTRADOR')
                    <button type="button" onclick='editAmbiente({{ json_encode($amb, JSON_UNESCAPED_UNICODE) }})' title="Editar ambiente"
                        style="background:#fef9c3; color:#ca8a04; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #fde047; display: flex; align-items: center; justify-content: center; font-size: 14px; cursor:pointer;">
                        ✏️
                    </button>

                    <form id="delete-form-{{ $amb->id_ambiente }}" method="POST" action="{{ route('ambientes.destroy', ['id_carrera' => $carrera->id_carrera, 'id_ambiente' => $amb->id_ambiente]) }}" style="margin: 0; display: flex;">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="openDeleteModal('{{ $amb->id_ambiente }}')" title="Eliminar ambiente"
                            style="background:#fee2e2; color:#dc2626; width: 32px; height: 32px; border-radius: 8px; border: 1px solid #fca5a5; display: flex; align-items: center; justify-content: center; font-size: 14px; cursor:pointer;">
                            🗑️
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
</div>
        @else
            <p style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 600;">No hay ambientes registrados para esta carrera.</p>
        @endif
    </section>

    {{-- ============================================================ --}}
    {{-- MODAL ASIGNAR CUSTODIOS (BÚSQUEDA POR ÍTEM, C.I., NOMBRE) --}}
    {{-- ============================================================ --}}
    <div id="modal-asignar-custodios" style="display: none !important; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10005; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: white; border-radius: 20px; padding: 25px; width: 100%; max-width: 550px; max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <h3 id="modal-ambiente-titulo" style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a;">Asignar Custodios al Ambiente</h3>
                <button type="button" onclick="closeAsignarCustodiosModal()" style="background: #f1f5f9; border: none; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 16px; cursor: pointer; color: #475569;">×</button>
            </div>

            <form id="form-asignar-custodios" method="POST">
                @csrf

                {{-- Buscador instantáneo --}}
                <div style="margin-bottom: 12px;">
                    <input type="text" id="buscador-docentes" placeholder="🔍 Buscar por Nombre, C.I. o N° de Ítem..."
                           style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; box-sizing: border-box;"
                           onkeyup="filtrarDocentesModal(this.value)">
                </div>

                {{-- Lista con checkboxes de usuarios activos --}}
                <div id="lista-docentes-container" style="max-height: 240px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; background: #f8fafc; margin-bottom: 15px;">
                    @foreach(\App\Models\User::where('estado', 'ACTIVO')->with(['items' => fn($q) => $q->whereNull('item_usuario.fecha_fin')])->orderBy('nombre_completo')->get() as $docente)
                        @php
                            $itemActivo = $docente->items->first();
                            $nroItem = $itemActivo ? 'Ítem #' . $itemActivo->numero_item : 'Sin ítem';
                            $cadenaBusqueda = strtolower($docente->nombre_completo . ' ' . $docente->ci . ' ' . ($itemActivo?->numero_item ?? ''));
                        @endphp
                        <label class="docente-item" data-busqueda="{{ $cadenaBusqueda }}"
                               style="display: flex; align-items: center; gap: 10px; padding: 8px; border-bottom: 1px solid #e2e8f0; cursor: pointer; background: white; border-radius: 6px; margin-bottom: 4px;">
                            <input type="checkbox" name="usuarios[]" value="{{ $docente->id_usuario }}" class="checkbox-docente" style="width: 16px; height: 16px;">
                            <div style="font-size: 12px;">
                                <strong style="color: #0f172a; display: block;">{{ $docente->nombre_completo }}</strong>
                                <span style="color: #64748b;">C.I.: {{ $docente->ci }} · <span style="color: #2563eb; font-weight: 600;">{{ $nroItem }}</span></span>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                    <button type="button" onclick="closeAsignarCustodiosModal()" style="background: #e2e8f0; color: #475569; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px;">Cancelar</button>
                    <button type="submit" style="background: #2563eb; color: white; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px;">💾 Guardar Asignación</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal flotante para crear/editar ambiente -->
    <div id="ambiente-modal" style="display: none !important; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: white; border-radius: 20px; padding: 30px; width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <h3 id="ambiente-modal-title" style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Nuevo ambiente</h3>
                <button type="button" onclick="closeAmbienteModal()" style="background: #f1f5f9; border: none; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 18px; cursor: pointer; color: #475569;">×</button>
            </div>

            <form id="ambiente-form" method="POST" action="{{ old('_method') === 'PUT' && old('ambiente_id') ? route('ambientes.update', ['id_carrera' => $carrera->id_carrera, 'id_ambiente' => old('ambiente_id')]) : route('ambientes.store', ['id_carrera' => $carrera->id_carrera]) }}">
                @csrf
                <input id="ambiente-method" type="hidden" name="_method" value="{{ old('_method', 'POST') }}">
                <input id="ambiente-id" type="hidden" name="ambiente_id" value="{{ old('ambiente_id') }}">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 15px;">
                    <div>
                        <label for="amb-codigo" class="mb-1 block text-xs font-bold text-slate-700 dark:text-slate-200">Código *</label>
                        <input id="amb-codigo" name="codigo" value="{{ old('codigo') }}" maxlength="30" aria-describedby="amb-codigo-error" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $errors->has('codigo') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-200' }}">
                        @error('codigo')
                            <p id="amb-codigo-error" class="mt-1 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="amb-nombre" class="mb-1 block text-xs font-bold text-slate-700 dark:text-slate-200">Nombre *</label>
                        <input id="amb-nombre" name="nombre" value="{{ old('nombre') }}" maxlength="100" aria-describedby="amb-nombre-error" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $errors->has('nombre') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-200' }}">
                        @error('nombre')
                            <p id="amb-nombre-error" class="mt-1 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="amb-bloque" class="mb-1 block text-xs font-bold text-slate-700 dark:text-slate-200">Bloque *</label>
                        <input id="amb-bloque" name="bloque" value="{{ old('bloque') }}" maxlength="20" aria-describedby="amb-bloque-error" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $errors->has('bloque') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-200' }}">
                        @error('bloque')
                            <p id="amb-bloque-error" class="mt-1 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="amb-piso" class="mb-1 block text-xs font-bold text-slate-700 dark:text-slate-200">Piso *</label>
                        <select id="amb-piso" name="piso" aria-describedby="amb-piso-error" class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 {{ $errors->has('piso') ? 'border-red-500 focus:border-red-500 focus:ring-red-200' : 'border-slate-300 focus:border-blue-500 focus:ring-blue-200' }}">
                            <option value="">— Seleccionar piso —</option>
                            <option value="PB" {{ old('piso') == 'PB' ? 'selected' : '' }}>PB</option>
                            <option value="1" {{ old('piso') == '1' ? 'selected' : '' }}>PISO 1</option>
                            <option value="2" {{ old('piso') == '2' ? 'selected' : '' }}>PISO 2</option>
                            <option value="3" {{ old('piso') == '3' ? 'selected' : '' }}>PISO 3</option>
                            <option value="4" {{ old('piso') == '4' ? 'selected' : '' }}>PISO 4</option>
                            <option value="5" {{ old('piso') == '5' ? 'selected' : '' }}>PISO 5</option>
                            <option value="6" {{ old('piso') == '6' ? 'selected' : '' }}>PISO 6</option>
                            <option value="7" {{ old('piso') == '7' ? 'selected' : '' }}>PISO 7</option>
                            <option value="8" {{ old('piso') == '8' ? 'selected' : '' }}>PISO 8</option>
                            <option value="9" {{ old('piso') == '9' ? 'selected' : '' }}>PISO 9</option>
                            <option value="10" {{ old('piso') == '10' ? 'selected' : '' }}>PISO 10</option>
                        </select>
                        @error('piso')
                            <p id="amb-piso-error" class="mt-1 text-xs font-medium text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 15px;">
                    <button type="button" onclick="closeAmbienteModal()" class="rounded-lg bg-slate-200 px-4 py-2.5 font-bold text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal pequeño de confirmación para eliminar -->
    <div id="delete-confirm-modal" style="display: none !important; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: white; border-radius: 16px; padding: 25px; width: 100%; max-width: 380px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); text-align: center;">
            <div style="font-size: 32px; margin-bottom: 10px;">⚠️</div>
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 800; color: #0f172a;">¿Eliminar ambiente?</h3>
            <p style="font-size: 13px; color: #64748b; margin: 0 0 20px;">Esta acción no se puede deshacer y borrará los datos asociados.</p>
            <div style="display: flex; justify-content: center; gap: 10px;">
                <button type="button" onclick="closeDeleteModal()" style="background: #e2e8f0; color: #475569; border: none; padding: 9px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px;">Cancelar</button>
                <button type="button" id="confirm-delete-btn" style="background: #dc2626; color: white; border: none; padding: 9px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px;">Sí, eliminar</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let currentAmbienteIdToDelete = null;

        // Modal Asignar Custodios
        function openAsignarCustodiosModal(ambienteId, ambienteNombre, custodiosActuales) {
            const modal = document.getElementById('modal-asignar-custodios');
            const form = document.getElementById('form-asignar-custodios');

            form.action = '{{ url("/ambientes") }}/' + ambienteId + '/custodios';
            document.getElementById('modal-ambiente-titulo').textContent = 'Asignar Custodios a: ' + ambienteNombre;

            document.querySelectorAll('.checkbox-docente').forEach(chk => {
                chk.checked = custodiosActuales.includes(parseInt(chk.value));
            });

            document.getElementById('buscador-docentes').value = '';
            filtrarDocentesModal('');

            modal.style.setProperty('display', 'flex', 'important');
        }

        function closeAsignarCustodiosModal() {
            document.getElementById('modal-asignar-custodios').style.setProperty('display', 'none', 'important');
        }

        function filtrarDocentesModal(texto) {
            const filtro = texto.toLowerCase().trim();
            document.querySelectorAll('.docente-item').forEach(item => {
                const busqueda = item.getAttribute('data-busqueda') || "";
                if (busqueda.includes(filtro)) {
                    item.style.display = "flex";
                } else {
                    item.style.display = "none";
                }
            });
        }

        // Modal Ambiente
        function openAmbienteModal() {
            const form = document.getElementById('ambiente-form');
            form.reset();
            form.action = '{{ route('ambientes.store', ['id_carrera' => $carrera->id_carrera]) }}';
            document.getElementById('ambiente-method').value = 'POST';
            document.getElementById('ambiente-id').value = '';
            document.getElementById('ambiente-modal-title').textContent = 'Nuevo ambiente';
            document.getElementById('ambiente-modal').style.setProperty('display', 'flex', 'important');
        }

        function closeAmbienteModal() {
            document.getElementById('ambiente-modal').style.setProperty('display', 'none', 'important');
        }

        function editAmbiente(serialized) {
            const amb = typeof serialized === 'string' ? JSON.parse(serialized) : serialized;
            const form = document.getElementById('ambiente-form');
            const carreraId = '{{ $carrera->id_carrera }}';
            const ambienteId = amb.id_ambiente;

            form.action = '{{ url('/carreras') }}/' + carreraId + '/ambientes/' + ambienteId;
            document.getElementById('ambiente-method').value = 'PUT';
            document.getElementById('ambiente-id').value = ambienteId;
            document.getElementById('ambiente-modal-title').textContent = 'Editar ambiente';
            document.getElementById('amb-codigo').value = amb.codigo || '';
            document.getElementById('amb-nombre').value = amb.nombre || '';
            document.getElementById('amb-bloque').value = amb.bloque || '';
            document.getElementById('amb-piso').value = amb.piso || '';
            document.getElementById('ambiente-modal').style.setProperty('display', 'flex', 'important');
        }

        function openDeleteModal(ambienteId) {
            currentAmbienteIdToDelete = ambienteId;
            document.getElementById('delete-confirm-modal').style.setProperty('display', 'flex', 'important');
        }

        function closeDeleteModal() {
            currentAmbienteIdToDelete = null;
            document.getElementById('delete-confirm-modal').style.setProperty('display', 'none', 'important');
        }

        document.getElementById('confirm-delete-btn').addEventListener('click', function() {
            if (currentAmbienteIdToDelete) {
                document.getElementById('delete-form-' + currentAmbienteIdToDelete).submit();
            }
        });

        @if(request('editar_ambiente'))
            @foreach($ambientes as $ambiente)
                @if((int) request('editar_ambiente') === (int) $ambiente->id_ambiente)
                    editAmbiente(@json($ambiente));
                @endif
            @endforeach
        @endif
    </script>

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ambienteId = @json(old('ambiente_id'));
                const form = document.getElementById('ambiente-form');
                if (ambienteId && @json(old('_method') === 'PUT')) {
                    form.action = '{{ url('/carreras') }}/{{ $carrera->id_carrera }}/ambientes/' + ambienteId;
                    document.getElementById('ambiente-modal-title').textContent = 'Editar ambiente';
                }
                document.getElementById('ambiente-modal').style.setProperty('display', 'flex', 'important');
            });
        </script>
    @endif
@endsection
