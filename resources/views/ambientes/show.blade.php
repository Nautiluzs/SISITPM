@extends('layouts.app')

@section('content')
@php use Illuminate\Support\Str; @endphp

    @if (session('success'))
        <div style="background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <svg style="width: 20px; height: 20px; color: #16a34a;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ============ ENCABEZADO DEL AMBIENTE ============ --}}
    <section class="hero-card" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 30px; border-radius: 16px; color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);">
        <div>
            <div style="display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,0.18);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Ambiente</div>
            <h1 style="margin:10px 0 8px;font-size:28px;">{{ $ambiente->nombre }} <span style="font-size: 18px; opacity: 0.85;">({{ $ambiente->codigo ?? 'S/C' }})</span></h1>
            <p>Administra las categorías y activos de este espacio.</p>
        </div>
        @php $custodiosAmbiente = $ambiente->custodios->where('estado', 'ACTIVO')->sortBy('nombre_completo'); @endphp
        @if ($custodiosAmbiente->count())
            <div style="display:flex;flex-direction:column;gap:6px;">
                <div style="font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;">👥 Docentes Custodios</div>
                <ul style="margin:0;padding:0;list-style:none;display:flex;flex-wrap:wrap;gap:8px;">
                    @foreach ($custodiosAmbiente as $c)
                        <li style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);padding:6px 12px;border-radius:20px;font-size:12px;color:white;font-weight:600;">
                            • {{ $c->nombre_completo }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div style="margin-top:8px;">
                <span style="font-size:12px;color:rgba(255,255,255,0.7);">👥 Sin docentes custodios asignados</span>
            </div>
        @endif
        <div class="stat-grid" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <div style="background: rgba(15, 23, 42, 0.35); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 16px; border-radius: 12px; text-align: center; min-width: 80px;">
                <div style="font-size: 18px; font-weight: 800; color: white;">{{ $categorias->count() }}</div>
                <span style="font-size: 9px; font-weight: 700; opacity: 0.8; text-transform: uppercase;">Categorías</span>
            </div>
            <div style="background: rgba(15, 23, 42, 0.35); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 16px; border-radius: 12px; text-align: center; min-width: 80px;">
                <div style="font-size: 18px; font-weight: 800; color: white;">{{ $ambiente->activos_count ?? 0 }}</div>
                <span style="font-size: 9px; font-weight: 700; opacity: 0.8; text-transform: uppercase;">Activos</span>
            </div>
            <div style="background: rgba(15, 23, 42, 0.35); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 16px; border-radius: 12px; text-align: center; min-width: 80px;">
                <div style="font-size: 18px; font-weight: 800; color: white;">{{ $ambiente->bloque ?? 'N/A' }}</div>
                <span style="font-size: 9px; font-weight: 700; opacity: 0.8; text-transform: uppercase;">Bloque</span>
            </div>
            <div style="background: rgba(15, 23, 42, 0.35); border: 1px solid rgba(255, 255, 255, 0.1); padding: 12px 16px; border-radius: 12px; text-align: center; min-width: 80px;">
                <div style="font-size: 18px; font-weight: 800; color: white;">{{ $ambiente->piso ?? 'N/D' }}</div>
                <span style="font-size: 9px; font-weight: 700; opacity: 0.8; text-transform: uppercase;">Piso</span>
            </div>
        </div>
    </section>

    {{-- ============ SECCIÓN DE CATEGORÍAS ============ --}}
    <section class="panel" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-bottom:18px;">
            <div>
                <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">📁 Categorías en este Ambiente</h2>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Selecciona el botón Ingresar de la categoría que deseas abrir.</p>
            </div>

            @if (auth()->check() && auth()->user()->rol === 'ADMINISTRADOR')
                <button type="button" onclick="openCategoriaModal()" style="background: #2563eb; color: white; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(37,99,235,0.2);">+ Nueva Categoría</button>
            @endif
        </div>

        @if ($categorias->count())
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
                }
                .folder-card:hover {
                    transform: translateY(-3px);
                    border-color: #2563eb;
                    box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.1);
                }
            </style>

            <div class="folders-grid">
                @foreach ($categorias as $cat)
                    <div class="folder-card" style="position:relative;">
                        <span style="position:absolute;top:12px;left:12px;color:#000;font-size:18px;font-weight:900;z-index:2;">
                            {{ $cat->id_categoria }}
                        </span>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding-top: 24px;">
                                <span style="font-size: 28px;">📁</span>
                                <span style="font-size: 10px; font-weight: 800; background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 6px;">
                                    {{ $cat->activos_count ?? 0 }} activos
                                </span>
                            </div>

                            <div style="margin-bottom: 4px;">
                                <span style="font-size: 10px; font-weight: 800; background: #eff6ff; color: #1d4ed8; padding: 2px 6px; border-radius: 4px;">
                                    {{ $cat->codigo }}
                                </span>
                            </div>

                            <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0;">{{ $cat->nombre }}</h3>
                            <p style="font-size: 11px; color: #64748b; margin: 0 0 10px 0;">{{ $cat->descripcion ? Str::limit($cat->descripcion, 60) : 'Sin descripción registrada.' }}</p>
                        </div>

                        <div style="display: flex; gap: 6px; justify-content: flex-end; border-top: 1px solid #f1f5f9; padding-top: 10px;" onclick="event.stopPropagation();">
                            <a href="{{ route('categorias.show', $cat->id_categoria) }}"
                               style="flex:1;text-align:center;background:#1e293b;color:#fff;padding:8px 7px;border-radius:8px;font-weight:800;text-decoration:none;font-size:11px;">
                                Ingresar
                            </a>
                            @if (auth()->check() && auth()->user()->rol === 'ADMINISTRADOR')
                                <button type="button" onclick='editCategoria({{ json_encode($cat, JSON_UNESCAPED_UNICODE) }})' title="Editar categoría"
                                    style="flex:1;background:#f59e0b; color:#111827; padding:8px 7px;border:0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;cursor:pointer;">
                                    Editar
                                </button>

                                <form id="delete-cat-form-{{ $cat->id_categoria }}" method="POST" action="{{ route('categorias.destroy', $cat->id_categoria) }}" style="margin: 0; display: flex;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="openDeleteCategoriaModal('{{ $cat->id_categoria }}')" title="Eliminar categoría"
                                        style="flex:1;background:#ef0040;color:#fff;border:0;padding:8px 7px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;cursor:pointer;">
                                        Eliminar
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="text-align: center; padding: 40px; color: #94a3b8; font-weight: 600;">No hay categorías registradas en este ambiente.</p>
        @endif
    </section>

    {{-- Modal flotante para crear/editar categoría --}}
    <div id="categoria-modal" style="display: none !important; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: white; border-radius: 20px; padding: 30px; width: 100%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); position: relative;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <h3 id="categoria-modal-title" style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a;">Nueva Categoría</h3>
                <button type="button" onclick="closeCategoriaModal()" style="background: #f1f5f9; border: none; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 18px; cursor: pointer; color: #475569;">×</button>
            </div>

            <form id="categoria-form" method="POST" action="{{ route('categorias.store') }}">
                @csrf
                <input id="categoria-method" type="hidden" name="_method" value="POST">
                <input type="hidden" name="id_ambiente" value="{{ $ambiente->id_ambiente }}">

                <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 15px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px; color: #334155;">Código *</label>
                        <input id="cat-codigo" name="codigo" value="{{ old('codigo') }}" maxlength="20" aria-describedby="cat-codigo-error" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid {{ $errors->has('codigo') ? '#dc2626' : '#cbd5e1' }}; box-sizing: border-box;">
                        @error('codigo')
                            <span id="cat-codigo-error" style="display:block;margin-top:5px;color:#dc2626;font-size:11px;font-weight:700;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px; color: #334155;">Nombre *</label>
                        <input id="cat-nombre" name="nombre" value="{{ old('nombre') }}" maxlength="100" aria-describedby="cat-nombre-error" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid {{ $errors->has('nombre') ? '#dc2626' : '#cbd5e1' }}; box-sizing: border-box;">
                        @error('nombre')
                            <span id="cat-nombre-error" style="display:block;margin-top:5px;color:#dc2626;font-size:11px;font-weight:700;">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 4px; color: #334155;">Descripción</label>
                        <textarea id="cat-descripcion" name="descripcion" rows="3" maxlength="255" aria-describedby="cat-descripcion-error" style="width: 100%; padding: 9px 12px; border-radius: 8px; border: 1px solid {{ $errors->has('descripcion') ? '#dc2626' : '#cbd5e1' }}; box-sizing: border-box; resize: none;">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <span id="cat-descripcion-error" style="display:block;margin-top:5px;color:#dc2626;font-size:11px;font-weight:700;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 15px;">
                    <button type="button" onclick="closeCategoriaModal()" style="background: #e2e8f0; color: #475569; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer;">Cancelar</button>
                    <button type="submit" style="background: #2563eb; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; cursor: pointer;">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal confirmación eliminar categoría -->
    <div id="delete-cat-confirm-modal" style="display: none !important; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; justify-content: center; align-items: center; padding: 20px;">
        <div style="background: white; border-radius: 16px; padding: 25px; width: 100%; max-width: 380px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); text-align: center;">
            <div style="font-size: 32px; margin-bottom: 10px;">⚠️</div>
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 800; color: #0f172a;">¿Eliminar categoría?</h3>
            <p style="font-size: 13px; color: #64748b; margin: 0 0 20px;">Esta acción borrará los registros asociados si no tienen dependencias activas.</p>
            <div style="display: flex; justify-content: center; gap: 10px;">
                <button type="button" onclick="closeDeleteCategoriaModal()" style="background: #e2e8f0; color: #475569; border: none; padding: 9px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px;">Cancelar</button>
                <button type="button" id="confirm-delete-cat-btn" style="background: #dc2626; color: white; border: none; padding: 9px 16px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 13px;">Sí, eliminar</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let currentCatIdToDelete = null;

        function openCategoriaModal() {
            const form = document.getElementById('categoria-form');
            form.reset();
            form.action = '{{ route('categorias.store') }}';
            document.getElementById('categoria-method').value = 'POST';
            document.getElementById('categoria-modal-title').textContent = 'Nueva Categoría';
            document.getElementById('cat-codigo').value = '';
            document.getElementById('cat-nombre').value = '';
            document.getElementById('cat-descripcion').value = '';
            document.getElementById('categoria-modal').style.setProperty('display', 'flex', 'important');
        }

        function closeCategoriaModal() {
            document.getElementById('categoria-modal').style.setProperty('display', 'none', 'important');
        }

        function editCategoria(serialized) {
            const cat = typeof serialized === 'string' ? JSON.parse(serialized) : serialized;
            const form = document.getElementById('categoria-form');

            form.action = '{{ url('/categorias') }}/' + cat.id_categoria;
            document.getElementById('categoria-method').value = 'PUT';
            document.getElementById('categoria-modal-title').textContent = 'Editar Categoría';
            document.getElementById('cat-codigo').value = cat.codigo || '';
            document.getElementById('cat-nombre').value = cat.nombre || '';
            document.getElementById('cat-descripcion').value = cat.descripcion || '';
            document.getElementById('categoria-modal').style.setProperty('display', 'flex', 'important');
        }

        function openDeleteCategoriaModal(catId) {
            currentCatIdToDelete = catId;
            document.getElementById('delete-cat-confirm-modal').style.setProperty('display', 'flex', 'important');
        }

        function closeDeleteCategoriaModal() {
            currentCatIdToDelete = null;
            document.getElementById('delete-cat-confirm-modal').style.setProperty('display', 'none', 'important');
        }

        document.getElementById('confirm-delete-cat-btn').addEventListener('click', function() {
            if (currentCatIdToDelete) {
                document.getElementById('delete-cat-form-' + currentCatIdToDelete).submit();
            }
        });

        @if(request('editar_categoria'))
            @foreach($categorias as $categoria)
                @if((int) request('editar_categoria') === (int) $categoria->id_categoria)
                    editCategoria(@json($categoria));
                @endif
            @endforeach
        @endif

        @if ($errors->hasAny(['codigo', 'nombre', 'descripcion', 'id_ambiente']))
            document.getElementById('categoria-modal').style.setProperty('display', 'flex', 'important');
        @endif
    </script>
@endsection
