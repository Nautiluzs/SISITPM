@extends('layouts.app')

@section('title', 'Inventario por Aula')

@section('content')
<div class="container-fluid px-4 py-6">
    <div style="max-width: 1200px; margin: 0 auto;">

        <a href="{{ route('reportes.index') }}"
           style="display: inline-flex; align-items: center; gap: 6px; color: #64748b; font-size: 13px; font-weight: 700; text-decoration: none; margin-bottom: 16px;">
            ← Volver a Reportes
        </a>

        {{-- ENCABEZADO --}}
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 25px 30px; border-radius: 16px; color: white; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; box-shadow: 0 10px 20px -3px rgba(15, 23, 42, 0.2);">
            <div>
                <span style="background: rgba(255, 255, 255, 0.15); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Detalle de Aula</span>
                <h1 style="font-size: 24px; font-weight: 800; margin: 6px 0 4px 0;">{{ $ambiente->nombre }}</h1>
                <p style="opacity: 0.85; font-size: 13px; margin: 0;">{{ optional($ambiente->carrera)->nombre ?? 'Sin carrera asociada' }}</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="{{ route('reportes.aula.pdf', array_merge(['id_ambiente' => $ambiente->id_ambiente], request()->query())) }}" target="_blank"
                   style="background: #dc2626; color: white; padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);">
                    📄 PDF
                </a>
                <a href="{{ route('reportes.aula.excel', array_merge(['id_ambiente' => $ambiente->id_ambiente], request()->query())) }}"
                   style="background: #059669; color: white; padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);">
                    📊 Excel
                </a>
            </div>
        </div>

        {{-- STATS --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div style="background: white; border: 1px solid #e2e8f0; padding: 14px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                <span style="background: #f1f5f9; color: #1e293b; padding: 6px 10px; border-radius: 8px; font-weight: 800; font-size: 13px;">{{ $stats['total'] }}</span>
                <span style="font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase;">Total</span>
            </div>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 14px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                <span style="background: #d1fae5; color: #065f46; padding: 6px 10px; border-radius: 8px; font-weight: 800; font-size: 13px;">{{ $stats['buenos'] }}</span>
                <span style="font-size: 12px; font-weight: 800; color: #065f46; text-transform: uppercase;">Buenos</span>
            </div>
            <div style="background: #fefce8; border: 1px solid #fde047; padding: 14px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                <span style="background: #fef08a; color: #854d0e; padding: 6px 10px; border-radius: 8px; font-weight: 800; font-size: 13px;">{{ $stats['regulares'] }}</span>
                <span style="font-size: 12px; font-weight: 800; color: #854d0e; text-transform: uppercase;">Regulares</span>
            </div>
            <div style="background: #fff7ed; border: 1px solid #fed7aa; padding: 14px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                <span style="background: #ffedd5; color: #9a3412; padding: 6px 10px; border-radius: 8px; font-weight: 800; font-size: 13px;">{{ $stats['malos'] }}</span>
                <span style="font-size: 12px; font-weight: 800; color: #9a3412; text-transform: uppercase;">Malos</span>
            </div>
            <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 14px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
                <span style="background: #fee2e2; color: #991b1b; padding: 6px 10px; border-radius: 8px; font-weight: 800; font-size: 13px;">{{ $stats['fuera'] }}</span>
                <span style="font-size: 12px; font-weight: 800; color: #991b1b; text-transform: uppercase;">Fuera Serv.</span>
            </div>
        </div>

        {{-- FILTROS --}}
        <div style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px;">
            <h2 style="font-size: 13px; font-weight: 800; color: #334155; text-transform: uppercase; margin-bottom: 12px;">Filtrar equipos</h2>
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: center;">
                <input type="text" name="nombre" value="{{ request('nombre') }}" placeholder="Buscar por nombre..."
                       style="padding: 10px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;">
                <select name="categoria_id" style="padding: 10px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id_categoria }}" {{ request('categoria_id') == $cat->id_categoria ? 'selected' : '' }}>
                            {{ $cat->nombre }}
                        </option>
                    @endforeach
                </select>
                <select name="estado_fisico" style="padding: 10px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none;">
                    <option value="">Todos los estados</option>
                    <option value="B"  {{ request('estado_fisico') == 'B'  ? 'selected' : '' }}>Bueno</option>
                    <option value="R"  {{ request('estado_fisico') == 'R'  ? 'selected' : '' }}>Regular</option>
                    <option value="M"  {{ request('estado_fisico') == 'M'  ? 'selected' : '' }}>Malo</option>
                    <option value="FF" {{ request('estado_fisico') == 'FF' ? 'selected' : '' }}>Fuera de servicio</option>
                </select>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" style="flex: 1; padding: 10px 16px; background: #2563eb; color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer;">
                        Filtrar
                    </button>
                    <a href="{{ route('reportes.aula', $ambiente->id_ambiente) }}" style="padding: 10px 16px; background: #e2e8f0; color: #334155; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none; text-align: center;">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        {{-- TABLA --}}
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                    <thead style="background: #f8fafc; color: #475569; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0;">
                        <tr>
                            <th style="padding: 14px 16px; width: 50px;">N°</th>
                            <th style="padding: 14px 16px;">Código</th>
                            <th style="padding: 14px 16px;">Descripción</th>
                            <th style="padding: 14px 16px; text-align: center;">Cant.</th>
                            <th style="padding: 14px 16px; text-align: center;">Estado</th>
                            <th style="padding: 14px 16px; text-align: center;">Fuente</th>
                            <th style="padding: 14px 16px; text-align: center;">Gestión</th>
                        </tr>
                    </thead>
                    <tbody style="divide-y: #f1f5f9;">
                        @forelse($agrupados as $i => $g)
                            <tr style="border-bottom: 1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                                <td style="padding: 14px 16px; color: #94a3b8; font-weight: 600; font-size: 12px;">{{ $i + 1 }}</td>
                                <td style="padding: 14px 16px; font-family: monospace; font-weight: 700; color: #334155; font-size: 12px;">{!! nl2br(e($g->codigos)) !!}</td>
                                <td style="padding: 14px 16px; font-weight: 700; color: #0f172a;">{{ $g->nombre }}</td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <span style="display: inline-block; padding: 4px 10px; background: #eff6ff; color: #1d4ed8; border-radius: 8px; font-weight: 800; font-size: 12px;">{{ $g->cantidad }}</span>
                                </td>
                                <td style="padding: 14px 16px; text-align: center;">
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 10px; background: #f1f5f9; color: #334155;">{{ $g->estado_texto }}</span>
                                </td>
                                <td style="padding: 14px 16px; text-align: center; font-size: 11px; font-weight: 700; color: #475569;">{{ $g->fuente_codigo }}</td>
                                <td style="padding: 14px 16px; text-align: center; font-weight: 700; color: #334155; font-size: 12px;">{{ $g->gestion }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="text-align: center; padding: 40px; color: #64748b; font-weight: 600;">Sin resultados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
