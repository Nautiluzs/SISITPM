@extends('layouts.app')

@section('title', 'Sin conexión')

@section('content')

{{-- ============================================================ --}}
{{-- ESTILOS PROPIOS (MISMO LENGUAJE QUE LOGIN Y PANEL) --}}
{{-- ============================================================ --}}
<style>
    .offline-card {
        position: relative;
        max-width: 620px;
        margin: 40px auto;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 44px 40px 36px;
        text-align: center;
        overflow: hidden;
        box-shadow:
            0 20px 40px -18px rgba(15, 23, 42, .18),
            0 0 0 1px rgba(255, 255, 255, .06);
        animation: offlineRise .5s cubic-bezier(.2, .7, .3, 1) both;
    }

    /* Línea superior degradada azul → rojo */
    .offline-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, #2563eb, #7c3aed 55%, #dc2626);
    }

    @keyframes offlineRise {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* Ícono con anillo degradado */
    .offline-icon {
        width: 96px;
        height: 96px;
        margin: 0 auto 20px;
        padding: 3px;
        border-radius: 26px;
        background: linear-gradient(135deg, #2563eb, #dc2626);
        box-shadow: 0 14px 28px -10px rgba(37, 99, 235, .55);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .offline-icon-inner {
        width: 100%;
        height: 100%;
        background: #ffffff;
        border-radius: 23px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
    }

    .offline-brand {
        text-align: center;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: #2563eb;
        margin-bottom: 6px;
    }

    .offline-title {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 10px 0;
        letter-spacing: -.01em;
    }

    .offline-desc {
        color: #475569;
        font-size: 14px;
        line-height: 1.65;
        margin: 0 auto 26px;
        max-width: 460px;
    }

    /* Caja de resumen de pendientes */
    .offline-summary {
        position: relative;
        background: linear-gradient(135deg, #eff6ff, #f8fafc 55%, #fef2f2);
        border: 1px solid #dbe3f0;
        border-radius: 14px;
        padding: 18px;
        margin-bottom: 24px;
    }
    .offline-summary::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 14px 14px 0 0;
        background: linear-gradient(90deg, #2563eb, #dc2626);
    }

    .offline-summary-label {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .offline-summary-num {
        margin: 6px 0 0 0;
        color: #0f172a;
        font-size: 30px;
        font-weight: 800;
        line-height: 1;
    }

    .offline-summary-sub {
        margin: 4px 0 0 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }

    /* Botones */
    .offline-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .offline-btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 22px;
        border-radius: 12px;
        color: #ffffff;
        font-weight: 700;
        font-size: 13.5px;
        text-decoration: none;
        background: linear-gradient(135deg, #1d4ed8, #2563eb 45%, #dc2626);
        box-shadow: 0 12px 22px -10px rgba(37, 99, 235, .6);
        transition: all .2s ease;
    }
    .offline-btn-primary:hover {
        transform: translateY(-2px);
        filter: brightness(1.06);
        box-shadow: 0 18px 28px -12px rgba(37, 99, 235, .7);
        color: #ffffff;
    }

    .offline-btn-ghost {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 22px;
        border-radius: 12px;
        background: #f8fafc;
        color: #1e3a8a;
        border: 1.5px solid #dbe3f0;
        font-weight: 700;
        font-size: 13.5px;
        cursor: pointer;
        transition: all .2s ease;
    }
    .offline-btn-ghost:hover {
        background: #eef4ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 10px 18px -12px rgba(37, 99, 235, .6);
    }

    .offline-hint {
        text-align: center;
        font-size: 11px;
        color: #94a3b8;
        margin-top: 20px;
    }

    @media (max-width: 520px) {
        .offline-card { padding: 34px 22px 28px; border-radius: 18px; margin: 20px auto; }
        .offline-title { font-size: 21px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .offline-card { animation: none; }
        * { transition: none !important; }
    }
</style>

<div class="container-fluid px-4 py-6">
    <div class="offline-card">

        {{-- Ícono SVG inline (no depende de conexión ni de imágenes externas) --}}
        <div class="offline-icon" aria-hidden="true">
            <div class="offline-icon-inner">
                <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                    <line x1="12" y1="20" x2="12.01" y2="20"/>
                    <line x1="2" y1="2" x2="22" y2="22"/>
                </svg>
            </div>
        </div>

        <p class="offline-brand">SISActivos · Modo Offline</p>
        <h1 class="offline-title">Estás sin conexión</h1>

        <p class="offline-desc">
            No te preocupes: lo que escanees se guarda en este equipo y se enviará solo
            en cuanto vuelva la señal. Puedes seguir verificando activos con normalidad.
        </p>

        {{-- Resumen de pendientes (JS lo actualiza) --}}
        <div id="offline-resumen" class="offline-summary">
            <p class="offline-summary-label">Sincronización pendiente</p>
            <p class="offline-summary-num">…</p>
            <p class="offline-summary-sub">Cargando pendientes…</p>
        </div>

        <div class="offline-actions">
            @if (auth()->check() && auth()->user()->puedeRegistrarVerificacion())
                <a href="{{ route('verificaciones.index') }}" class="offline-btn-primary">
                    🔍 Ir a verificación física
                </a>
            @endif
            <button type="button" onclick="window.location.reload()" class="offline-btn-ghost">
                🔄 Reintentar ahora
            </button>
        </div>

        <p class="offline-hint">
            Los datos se sincronizan automáticamente al recuperar la conexión
        </p>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.InventarioOffline) {
        return;
    }

    window.InventarioOffline.pendientes().then(function (total) {
        const resumen = document.getElementById('offline-resumen');
        if (!resumen) return;

        const vacio = !total || total === 0;

        resumen.innerHTML =
            '<p class="offline-summary-label">Sincronización pendiente</p>' +
            '<p class="offline-summary-num" style="' +
                (vacio ? 'color:#166534;' : '') +
            '">' + total + '</p>' +
            '<p class="offline-summary-sub">' +
                (vacio
                    ? 'Todo está sincronizado ✓'
                    : 'verificaciones esperando enviarse') +
            '</p>';
    });
});
</script>
@endpush
@endsection