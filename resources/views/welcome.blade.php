@extends('layouts.app')

@section('title', 'Inicio - Panel Principal')

@section('content')

{{-- ============================================================ --}}
{{-- ESTILOS PROPIOS DEL PANEL (MISMO LENGUAJE QUE EL LOGIN) --}}
{{-- ============================================================ --}}
<style>
    .sis-card {
        position: relative;
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(15, 23, 42, .03), 0 2px 4px -2px rgba(15, 23, 42, .04);
        overflow: hidden;
        padding: 24px;
    }
    .sis-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, #2563eb, #7c3aed 55%, #dc2626);
    }
    .sis-card-title {
        font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;
    }
    .sis-card-sub {
        font-size: 12px; color: #64748b; margin: 2px 0 0 0;
    }

    .sis-hero {
        position: relative;
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 45%, #b91c1c 100%);
        border-radius: 20px;
        color: #fff;
        padding: 32px;
        display: grid;
        grid-template-columns: 1.2fr .8fr;
        gap: 24px;
        align-items: center;
        margin-bottom: 25px;
        overflow: hidden;
        box-shadow: 0 20px 40px -20px rgba(37, 99, 235, .55);
    }
    .sis-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(700px 360px at 88% 12%, rgba(255,255,255,.15), transparent 60%),
            radial-gradient(560px 320px at 8% 92%, rgba(0,0,0,.28), transparent 60%);
        pointer-events: none;
    }
    .sis-hero > * { position: relative; z-index: 1; }

    .sis-pill {
        display: inline-block;
        padding: 5px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .18);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin-bottom: 12px;
        border: 1px solid rgba(255,255,255,.22);
        backdrop-filter: blur(6px);
    }

    .sis-stat {
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .22);
        border-radius: 14px;
        padding: 16px;
        backdrop-filter: blur(6px);
        transition: transform .2s ease, background .2s ease;
    }
    .sis-stat:hover {
        background: rgba(255, 255, 255, .18);
        transform: translateY(-2px);
    }
    .sis-stat-num { font-size: 26px; font-weight: 800; line-height: 1.2; }
    .sis-stat-label {
        font-size: 11px;
        color: rgba(255, 255, 255, .85);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-top: 4px;
    }

    .sis-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 20px;
        border-radius: 12px;
        color: #ffffff;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        background: linear-gradient(135deg, #1d4ed8, #2563eb 45%, #dc2626);
        box-shadow: 0 12px 22px -10px rgba(37, 99, 235, .6);
        transition: all .2s ease;
    }
    .sis-btn-primary:hover {
        transform: translateY(-2px);
        filter: brightness(1.06);
        box-shadow: 0 18px 28px -12px rgba(37, 99, 235, .7);
        color: #ffffff;
    }

    .sis-status-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 11px 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        text-decoration: none;
        color: #1e293b;
        font-size: 13px;
        font-weight: 700;
        transition: all .2s ease;
    }
    .sis-status-row:hover {
        background: #eef4ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
        transform: translateX(2px);
        box-shadow: 0 8px 16px -12px rgba(37, 99, 235, .55);
    }
    .sis-badge {
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 800;
    }
    .sis-badge-good  { background: #dcfce7; color: #166534; }
    .sis-badge-rep   { background: #fef9c3; color: #854d0e; }
    .sis-badge-maint { background: #ffedd5; color: #9a3412; }
    .sis-badge-out   { background: #fee2e2; color: #991b1b; }

    .sis-chip {
        font-size: 11px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        padding: 4px 10px;
        border-radius: 8px;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .sis-activity {
        padding: 11px 14px;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        border-left: 3px solid #2563eb;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        transition: background .15s ease, border-color .15s ease;
    }
    .sis-activity:hover {
        background: #eef4ff;
        border-left-color: #dc2626;
    }

    @media (max-width: 900px) {
        .sis-hero { grid-template-columns: 1fr; padding: 24px; }
        .sis-grid-2-1 { grid-template-columns: 1fr !important; }
    }
</style>

<div class="container-fluid px-4 py-4">

    {{-- ============================================================ --}}
    {{-- HERO CORPORATIVO SUPERIOR --}}
    {{-- ============================================================ --}}
    <div class="sis-hero">

        {{-- Lado Izquierdo --}}
        <div>
            <span class="sis-pill">Panel Institucional</span>
            <h1 style="margin: 0 0 8px 0; font-size: 26px; font-weight: 800; line-height: 1.2;">
                @auth
                    Bienvenido, {{ auth()->user()->nombre_completo ?? 'Administrador' }}
                @else
                    Bienvenido al Sistema SISActivos
                @endauth
            </h1>
            <p style="margin: 0 0 16px 0; color: rgba(255,255,255,.9); font-size: 13px; font-weight: 500;">
                Sistema de Control y Gestión de Inventarios de Laboratorio
            </p>
            <p style="margin: 0; color: rgba(255,255,255,.85); font-size: 13px; line-height: 1.5;">
                Monitoreo en tiempo real del estado físico de los bienes y flujos de activos registrados en el instituto.
            </p>
        </div>

        {{-- Lado Derecho: Tarjetas Estadísticas 2x2 --}}
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div class="sis-stat">
                <div class="sis-stat-num">{{ $totalActivos ?? 7 }}</div>
                <div class="sis-stat-label">Total Activos</div>
            </div>
            <div class="sis-stat">
                <div class="sis-stat-num" style="color:#86efac;">{{ $activosBuenos ?? 5 }}</div>
                <div class="sis-stat-label">En Buen Estado</div>
            </div>
            <div class="sis-stat">
                <div class="sis-stat-num" style="color:#fde047;">{{ $enReparacion ?? 0 }}</div>
                <div class="sis-stat-label">En Reparación</div>
            </div>
            <div class="sis-stat">
                <div class="sis-stat-num" style="color:#fca5a5;">{{ ($enMantenimiento ?? 0) + ($fueraServicio ?? 2) }}</div>
                <div class="sis-stat-label">Atención / Baja</div>
            </div>
        </div>
    </div>

    @if(auth()->check() && auth()->user()->esAdmin())
        <div style="display:flex; justify-content:flex-end; margin-bottom: 20px;">
            <a href="{{ route('carreras.create') }}" class="sis-btn-primary">
                ➕ Nueva Carrera
            </a>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- ACCESOS RÁPIDOS Y RESUMEN ACADÉMICO --}}
    {{-- ============================================================ --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:25px;">
        <a href="{{ route('activos.index') }}" class="sis-card" style="text-decoration:none;padding:20px;transition:transform .2s ease;">
            <div style="font-size:25px;margin-bottom:8px;">📦</div>
            <h2 class="sis-card-title">Gestionar activos</h2>
            <p class="sis-card-sub">Consulta, registra y actualiza el inventario.</p>
        </a>
        <a href="{{ route('carreras.index') }}" class="sis-card" style="text-decoration:none;padding:20px;transition:transform .2s ease;">
            <div style="font-size:25px;margin-bottom:8px;">🎓</div>
            <h2 class="sis-card-title">Carreras y ambientes</h2>
            <p class="sis-card-sub">{{ $totalCarreras ?? 0 }} carreras · {{ $totalAmbientes ?? 0 }} ambientes visibles.</p>
        </a>
        <a href="{{ route('categorias.index') }}" class="sis-card" style="text-decoration:none;padding:20px;transition:transform .2s ease;">
            <div style="font-size:25px;margin-bottom:8px;">📁</div>
            <h2 class="sis-card-title">Categorías</h2>
            <p class="sis-card-sub">Organiza los activos por tipo y ubicación.</p>
        </a>
        <a href="{{ route('reportes.index') }}" class="sis-card" style="text-decoration:none;padding:20px;transition:transform .2s ease;">
            <div style="font-size:25px;margin-bottom:8px;">📊</div>
            <h2 class="sis-card-title">Reportes</h2>
            <p class="sis-card-sub">Visualiza y descarga información del sistema.</p>
        </a>
    </div>

    <div class="sis-grid-2-1" style="display:grid;grid-template-columns:1.2fr .8fr;gap:20px;margin-bottom:25px;">
        <div class="sis-card">
            <h2 class="sis-card-title">Activos por carrera</h2>
            <p class="sis-card-sub" style="margin-bottom:16px;">Carreras con mayor cantidad de bienes registrados.</p>
            <div style="display:flex;flex-direction:column;gap:9px;">
                @forelse($activosPorCarrera ?? [] as $carrera)
                    <a href="{{ route('carreras.show', $carrera->id_carrera) }}" class="sis-status-row">
                        <span>{{ $carrera->nombre }}</span>
                        <span class="sis-chip">{{ $carrera->total_activos }} activos</span>
                    </a>
                @empty
                    <p class="sis-card-sub">No hay información disponible.</p>
                @endforelse
            </div>
        </div>
        <div class="sis-card">
            <h2 class="sis-card-title">Resumen de alcance</h2>
            <p class="sis-card-sub" style="margin-bottom:16px;">Información disponible para tu usuario.</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div class="sis-status-row" style="display:block;"><span class="sis-card-sub">Carreras</span><strong style="display:block;font-size:22px;">{{ $totalCarreras ?? 0 }}</strong></div>
                <div class="sis-status-row" style="display:block;"><span class="sis-card-sub">Ambientes</span><strong style="display:block;font-size:22px;">{{ $totalAmbientes ?? 0 }}</strong></div>
                <div class="sis-status-row" style="display:block;"><span class="sis-card-sub">Usuarios</span><strong style="display:block;font-size:22px;">{{ $totalUsuarios ?? 0 }}</strong></div>
                <div class="sis-status-row" style="display:block;"><span class="sis-card-sub">Activos</span><strong style="display:block;font-size:22px;">{{ $totalActivos ?? 0 }}</strong></div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- SECCIÓN CENTRAL: GRÁFICA + ESTADOS --}}
    {{-- ============================================================ --}}
    <div class="sis-grid-2-1" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px;">

        {{-- Gráfica Estadística Mensual --}}
        <div class="sis-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 12px;">
                <div>
                    <h2 class="sis-card-title">Flujo Mensual de Activos</h2>
                    <p class="sis-card-sub">Comparativa mensual de altas (nuevos) y bajas registradas</p>
                </div>
                <span class="sis-chip">Gestión {{ date('Y') }}</span>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="graficaActivosMensual"></canvas>
            </div>
        </div>

        {{-- Panel de Desglose por Estado Físico --}}
        <div class="sis-card" style="display: flex; flex-direction: column;">
            <div>
                <h2 class="sis-card-title" style="margin-bottom: 4px;">Estado de Inventario</h2>
                <p class="sis-card-sub" style="margin-bottom: 16px;">Distribución actual por condición física.</p>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="{{ route('activos.index') }}" class="sis-status-row">
                        <span>🟢 Buenos</span>
                        <span class="sis-badge sis-badge-good">{{ $activosBuenos ?? 5 }}</span>
                    </a>
                    <a href="{{ route('activos.index') }}" class="sis-status-row">
                        <span>🟡 En Reparación</span>
                        <span class="sis-badge sis-badge-rep">{{ $enReparacion ?? 0 }}</span>
                    </a>
                    <a href="{{ route('activos.index') }}" class="sis-status-row">
                        <span>🟠 Mantenimiento</span>
                        <span class="sis-badge sis-badge-maint">{{ $enMantenimiento ?? 0 }}</span>
                    </a>
                    <a href="{{ route('activos.index') }}" class="sis-status-row">
                        <span>🔴 Fuera de servicio</span>
                        <span class="sis-badge sis-badge-out">{{ $fueraServicio ?? 2 }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ÚLTIMAS ACTIVIDADES --}}
    {{-- ============================================================ --}}
    @if(!empty($recentActivities) && $recentActivities->count())
    <div class="sis-card">
        <h2 class="sis-card-title" style="margin-bottom: 14px;">Últimas Actividades Registradas</h2>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            @foreach($recentActivities as $act)
                <div class="sis-activity">
                    <span style="color: #334155; font-weight: 600;">
                        ⚡ <strong>{{ $act->accion }}</strong> en tabla <em>{{ $act->tabla_afectada }}</em> (ID: {{ $act->registro_id }})
                    </span>
                    <span style="color: #64748b; font-size: 11px; font-weight: 700; white-space: nowrap;">
                        {{ $act->fecha_cambio->format('d/m/Y H:i') }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

{{-- SCRIPT PARA RENDERIZAR LA GRÁFICA CON CHART.JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('graficaActivosMensual').getContext('2d');

        // Gradiente vertical azul para relleno de "Altas"
        const gradAltas = ctx.createLinearGradient(0, 0, 0, 260);
        gradAltas.addColorStop(0, 'rgba(37, 99, 235, 0.28)');
        gradAltas.addColorStop(1, 'rgba(37, 99, 235, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                datasets: [
                    {
                        label: 'Activos Nuevos (Altas)',
                        data: [2, 4, 3, 5, 7, 6, 8, 4, 6, 9, 5, 7],
                        borderColor: '#2563eb',
                        backgroundColor: gradAltas,
                        borderWidth: 3,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35
                    },
                    {
                        label: 'Activos de Baja',
                        data: [0, 1, 0, 2, 1, 0, 1, 2, 0, 1, 0, 1],
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.05)',
                        borderWidth: 2,
                        pointBackgroundColor: '#dc2626',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.35
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 11, weight: 'bold' },
                            color: '#475569',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        borderColor: 'rgba(255,255,255,.1)',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 11 }, color: '#64748b' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 }, color: '#64748b' }
                    }
                }
            }
        });
    });
</script>
@endsection