<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="SISActivos - Sistema de Control y Gestión de Activos Fijos">
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'SISActivos') - Sistema de Control y Gestión</title>
    <script>
        if (localStorage.getItem('sisactivos-theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
        document.documentElement.dataset.fontSize = localStorage.getItem('sisactivos-font-size') || 'normal';
        document.documentElement.dataset.colorTheme = localStorage.getItem('sisactivos-color-theme') || 'default';
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Cola local: permite escanear y registrar sin conexión --}}
    @vite(['resources/js/offline.js'])

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --sidebar-bg: #111c44;
            --sidebar-hover: #1b285a;
            --sidebar-active: #2563eb;
            --body-bg: #f4f7fe;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #8fa0b5;
        }

        html[data-font-size="small"] {
            font-size: 14px;
        }

        html[data-font-size="normal"] {
            font-size: 16px;
        }

        html[data-font-size="large"] {
            font-size: 18px;
        }

        html[data-font-size="small"] body {
            zoom: 0.9;
        }

        html[data-font-size="large"] body {
            zoom: 1.125;
        }

        html[data-color-theme="blue-red"] {
            --sidebar-bg: #0b1f4d;
            --sidebar-hover: #173b78;
            --sidebar-active: #dc2626;
        }

        html[data-color-theme="blue-red"] .brand-icon,
        html[data-color-theme="blue-red"] [class~="bg-blue-600"],
        html[data-color-theme="blue-red"] [style*="background: #2563eb"],
        html[data-color-theme="blue-red"] [style*="background:#2563eb"] {
            background-color: #dc2626 !important;
        }

        html[data-color-theme="blue-red"] .hero-card {
            background: linear-gradient(135deg, #123b8f 0%, #dc2626 100%) !important;
            box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.25) !important;
        }

        html[data-color-theme="blue-red"] .folder-card:hover {
            border-color: #dc2626 !important;
            box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.16) !important;
        }

        html[data-color-theme="blue-red"] .panel button[style*="background: #2563eb"],
        html[data-color-theme="blue-red"] .panel button[style*="background:#2563eb"] {
            background-color: #dc2626 !important;
        }

        html[data-color-theme="blue-red"] a[style*="background:#2563eb"],
        html[data-color-theme="blue-red"] a[style*="background: #2563eb"] {
            background-color: #dc2626 !important;
        }

        html[data-color-theme="blue-red"] [class~="border-blue-500"],
        html[data-color-theme="blue-red"] [class~="hover:border-blue-500"]:hover {
            border-color: #dc2626 !important;
        }

        html[data-color-theme="blue-red"] [class~="text-blue-600"],
        html[data-color-theme="blue-red"] [class~="text-blue-700"] {
            color: #b91c1c !important;
        }

        @layer base {
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
        }

        html,
        body {
            background-color: var(--body-bg);
            height: 100%;
            overflow-x: hidden;
        }

        body {
            display: flex;
        }

        /* ============ SIDEBAR ============ */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            flex-shrink: 0;
            overflow-y: auto;
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }

        .brand {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 25px;
            padding-left: 12px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-icon {
            display: inline-flex;
            width: 32px;
            height: 32px;
            background: #2563eb;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .menu-category {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            padding: 12px 12px 6px;
            letter-spacing: 0.08em;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            color: #d1d5db;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
        }

        .nav-item svg {
            flex-shrink: 0;
        }

        .nav-item:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
        }

        .nav-item.active {
            background-color: var(--sidebar-active);
            color: #ffffff;
        }

        .logout-form {
            margin-top: auto;
            padding-top: 20px;
        }

        .logout-form button {
            width: 100%;
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }

        .logout-form button:hover {
            background: #b91c1c;
        }

        /* ============ MAIN ============ */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
        }

        .top-navbar {
            background: #ffffff;
            min-height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 32px;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 40;
            gap: 16px;
            flex-wrap: wrap;
        }

        .content-area {
            padding: 24px 32px 32px;
        }

        /* ============ BOTÓN MOBILE MENU ============ */
        .mobile-menu-toggle {
            display: none;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #dbeafe;
            cursor: pointer;
            font-size: 20px;
            align-items: center;
            justify-content: center;
        }

        /* ============ OVERLAY MOBILE ============ */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 980px) {
            .sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                bottom: 0;
                z-index: 100;
                transition: left 0.3s;
            }

            .sidebar.open {
                left: 0;
            }

            .mobile-menu-toggle {
                display: inline-flex;
            }

            .top-navbar {
                padding: 12px 16px;
                flex-direction: row;
                align-items: center;
            }

            .content-area {
                padding: 16px;
            }
        }

        /* ============ LOADING BAR ============ */
        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #2563eb, #7c3aed, #2563eb);
            background-size: 200% 100%;
            animation: loading 1.5s linear infinite;
            z-index: 9999;
            display: none;
        }

        .loading-bar.active {
            display: block;
        }

        @keyframes loading {
            0% {
                background-position: 0% 0;
                width: 0%;
            }

            50% {
                background-position: 100% 0;
                width: 70%;
            }

            100% {
                background-position: 0% 0;
                width: 100%;
            }
        }

        /* ============ ANIMACIÓN DEL ICONO DE BIENVENIDA ============ */
        @keyframes successPop {
            0% {
                transform: scale(0) rotate(-180deg);
                opacity: 0;
            }

            50% {
                transform: scale(1.2) rotate(10deg);
                opacity: 1;
            }

            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        .success-pop {
            animation: successPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
    </style>

    @stack('styles')

    {{-- Tema oscuro opcional --}}
    <style>
        html.dark {
            color-scheme: dark;
        }
        html.dark body {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
        }
        html.dark .main-wrapper {
            background-color: #0f172a !important;
        }
        html.dark .panel,
        html.dark [style*="background: white"],
        html.dark [style*="background:white"],
        html.dark [style*="background: #ffffff"],
        html.dark [style*="background:#ffffff"] {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }
        html.dark [style*="color: #0f172a"],
        html.dark [style*="color:#0f172a"],
        html.dark [style*="color: #334155"],
        html.dark [style*="color:#334155"],
        html.dark [style*="color: #475569"],
        html.dark [style*="color:#475569"],
        html.dark [style*="color: #64748b"],
        html.dark [style*="color:#64748b"],
        html.dark [style*="color: #1e293b"],
        html.dark [style*="color:#1e293b"] {
            color: #e2e8f0 !important;
        }
        html.dark .content-area [class*="text-slate-"] {
            color: #e2e8f0 !important;
        }
        html.dark input, html.dark select, html.dark textarea {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }
        html.dark table thead tr,
        html.dark [style*="background:#f8fafc"],
        html.dark [style*="background: #f8fafc"] {
            background-color: #334155 !important;
        }
        html.dark .top-navbar {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }
    </style>
</head>

<body>

    {{-- Barra de carga global --}}
    <div class="loading-bar" id="loadingBar"></div>

    {{-- Overlay para menú móvil --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- ============ SIDEBAR ============ --}}
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="brand-icon">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </span>
            SISActivos
        </div>

        @auth
            @php $__user = auth()->user(); @endphp

            {{-- INICIO --}}
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Inicio
            </a>

            @if($__user->esAdmin())
                <a href="{{ route('carreras.index') }}" class="nav-item {{ request()->routeIs('carreras.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                    Carreras
                </a>
            @endif

            {{-- CARRERAS --}}
            <div class="menu-category">CARRERAS</div>
            @php
                $__idsCarreras = $__user->idsCarrerasVisibles();
                $__carreras = \App\Models\Carrera::when(
                    $__idsCarreras === null,
                    fn($q) => $q,
                    fn($q) => $q->whereIn('id_carrera', $__idsCarreras),
                )
                    ->orderBy('nombre')
                    ->get();
            @endphp

            @forelse($__carreras as $car)
                <a href="{{ route('carreras.show', $car->id_carrera) }}"
                    class="nav-item {{ request()->routeIs('carreras.show') && request()->route('id_carrera') == $car->id_carrera ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                    </svg>
                    {{ $car->nombre }}
                </a>
            @empty
                <div style="padding: 8px 14px; font-size: 12px; color: #64748b; font-style: italic;">
                    Sin carreras a su alcance
                </div>
            @endforelse

            {{-- CONTROL Y OPERACIONES --}}
            <div class="menu-category">CONTROL Y OPERACIONES</div>

            @if ($__user->puedeRegistrarVerificacion())
                <a href="{{ route('verificaciones.index') }}"
                    class="nav-item {{ request()->routeIs('verificaciones.*') && !request()->routeIs('verificaciones-aprobacion.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Verificación Física
                    <span id="offline-pendientes"
                        style="display: none; background: #f59e0b; color: white; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: auto; font-weight: 800;"
                        title="Verificaciones guardadas en este equipo, pendientes de enviar">0</span>
                </a>
            @endif

            @if ($__user->puedeAprobar())
                @php
                    $__pendientes =
                        \App\Models\ActaBaja::pendientes()->count() +
                        \App\Models\ActaTransferencia::pendientes()->count() +
                        \App\Models\VerificacionFisica::pendientesAprobacion()->count();
                @endphp
                <a href="{{ route('aprobaciones.index') }}"
                    class="nav-item {{ request()->routeIs('aprobaciones.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Aprobaciones
                    @if ($__pendientes > 0)
                        <span
                            style="background: #ef4444; color: white; font-size: 9px; padding: 2px 6px; border-radius: 4px; margin-left: auto; font-weight: 800;">{{ $__pendientes }}</span>
                    @endif
                </a>
            @endif

            @if ($__user->puedeSolicitarBajaOTransferencia())
                <a href="{{ route('actas.baja.index') }}"
                    class="nav-item {{ request()->routeIs('actas.baja.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Actas de Baja
                    @if ($__user->puedeAprobar())
                        <span
                            style="background: #ef4444; color: white; font-size: 9px; padding: 2px 6px; border-radius: 4px; margin-left: auto; font-weight: 800;">APRUEBA</span>
                    @endif
                </a>

                <a href="{{ route('actas.transferencia.index') }}"
                    class="nav-item {{ request()->routeIs('actas.transferencia.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    Transferencias
                    @if ($__user->puedeAprobar())
                        <span
                            style="background: #ef4444; color: white; font-size: 9px; padding: 2px 6px; border-radius: 4px; margin-left: auto; font-weight: 800;">APRUEBA</span>
                    @endif
                </a>
            @endif

            <a href="{{ route('categorias.index') }}"
                class="nav-item {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                Categorías
            </a>

            <a href="{{ route('activos.index') }}"
                class="nav-item {{ request()->routeIs('activos.*') && !request()->routeIs('activos.no-activos') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                Activos
            </a>

            {{-- No Activos --}}
            <a href="{{ route('activos.noActivos') }}"
                class="nav-item {{ request()->routeIs('activos.no-activos') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                No Activos
            </a>

            <a href="{{ route('activos.baja') }}"
                class="nav-item {{ request()->routeIs('activos.baja') ? 'active' : '' }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Activos de Baja
            </a>

            @if ($__user->esAdmin())
                <a href="{{ route('items.index') }}" class="nav-item {{ request()->routeIs('items.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Personal Asignado
                </a>
            @endif

            @if ($__user->esAdmin() || $__user->esOperativo() || $__user->esJefeCarrera() || $__user->esCustodio())
                <a href="{{ route('reportes.index') }}"
                    class="nav-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Reportes
                </a>
            @endif

            {{-- ADMINISTRACIÓN --}}
            @if ($__user->esAdmin())
                <div class="menu-category">ADMINISTRACIÓN</div>

                <a href="{{ route('backup.index') }}"
                    class="nav-item {{ request()->routeIs('backup.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    </svg>
                    Copias de Seguridad
                </a>
<a href="{{ route('activos.importar.form') }}" class="nav-item {{ request()->routeIs('activos.importar.*') ? 'active' : '' }}">
    <span>📂</span> Importar desde Excel/CSV
</a>
                <a href="{{ route('manual.index') }}" class="nav-item {{ request()->routeIs('manual.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Manual de Usuario
                </a>
                <a href="{{ route('configuracion.index') }}" class="nav-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                    ⚙️ Configuraciones
                </a>
            @endif

            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf
                <button type="submit">Cerrar sesión</button>
            </form>
        @endauth
    </aside>

    {{-- ============ CONTENT WRAPPER ============ --}}
    <div class="main-wrapper">

        {{-- ============ TOP NAVBAR ============ --}}
        <header class="top-navbar">

            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 280px; max-width: 800px;">

                <button type="button" class="mobile-menu-toggle" onclick="toggleSidebar()" title="Abrir menú">
                    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                {{-- ÚNICO BOTÓN "Volver" QUE REGRESA EXACTAMENTE DE DONDE SALISTE --}}
                @if (!request()->is('/') && !request()->is('dashboard'))
                    <button type="button" onclick="history.back()" title="Volver"
                        style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 42px; padding: 0 16px; border-radius: 10px; background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; font-weight: 700; font-size: 13px; cursor: pointer; white-space: nowrap; text-decoration: none; transition: all 0.2s ease;"
                        onmouseover="this.style.background='#dbeafe';" onmouseout="this.style.background='#eff6ff';">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Volver</span>
                    </button>
                @endif

                <form method="GET" action="{{ route('activos.index') }}"
                    style="display: flex; align-items: center; gap: 8px; flex: 1; margin: 0;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" name="buscar" value="{{ request('buscar') }}"
                            placeholder="Buscar activo por código, nombre, serie, marca..." autocomplete="off"
                            style="width: 100%; height: 42px; padding: 0 42px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; outline: none; background: #f8fafc; color: #0f172a; transition: all 0.2s ease;"
                            onfocus="this.style.background='#ffffff'; this.style.borderColor='#2563eb';"
                            onblur="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1';">
                        <span
                            style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px; pointer-events: none; display: inline-flex;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                    </div>

                    <button type="submit"
                        style="height: 42px; padding: 0 20px; border-radius: 10px; background: #2563eb; color: #ffffff; border: none; font-weight: 700; font-size: 13px; cursor: pointer; white-space: nowrap; transition: background 0.2s ease;"
                        onmouseover="this.style.background='#1d4ed8';" onmouseout="this.style.background='#2563eb';">
                        Buscar
                    </button>
                </form>
            </div>

            @auth
                <div class="top-user-area" style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
                    <div class="top-user-actions" style="display:flex;align-items:center;gap:8px;">
                        <button type="button" id="theme-toggle" title="Cambiar modo claro u oscuro"
                            style="width: 42px; height: 42px; background-color: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; cursor: pointer;">
                            <span id="theme-toggle-icon" aria-hidden="true">🌙</span>
                        </button>
                        @if (auth()->user()->esAdmin())
                            <a href="{{ route('usuarios.index') }}" title="Gestión de Usuarios"
                                style="width: 42px; height: 42px; background-color: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 18px; box-shadow: 0 2px 5px rgba(37, 99, 235, 0.15); transition: all 0.2s ease;"
                                onmouseover="this.style.backgroundColor='#2563eb'; this.style.color='#fff';"
                                onmouseout="this.style.backgroundColor='#eff6ff'; this.style.color='#2563eb';">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </a>
                        @endif
                    </div>

                    <div style="text-align: right; border-left: 1px solid #e2e8f0; padding-left: 16px;">
                        <span
                            style="display: block; font-size: 13px; font-weight: 700; color: #1e293b; white-space: nowrap;">
                            {{ auth()->user()->nombre_completo }}
                        </span>
                        <span style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">
                            {{ auth()->user()->rol }}
                        </span>
                    </div>
                </div>
                @endauth
        </header>

        <main class="content-area">
            @yield('content')
        </main>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL DE BIENVENIDA (aparece solo tras login exitoso) --}}
    {{-- ============================================================ --}}
    @if (session('login_success'))
        <div id="welcome-modal"
            style="position: fixed; inset: 0; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.3s ease;">

            <div id="welcome-card"
                style="position: relative; background: white; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); max-width: 420px; width: 100%; overflow: hidden; transform: scale(0.9); transition: transform 0.5s ease;">

                {{-- Barra superior decorativa --}}
                <div
                    style="height: 8px; background: linear-gradient(90deg, #34d399, #14b8a6, #34d399); animation: pulse 2s ease-in-out infinite;">
                </div>

                {{-- Botón cerrar --}}
                <button type="button" onclick="cerrarModalBienvenida()"
                    style="position: absolute; top: 16px; right: 16px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #f1f5f9; color: #475569; border: none; cursor: pointer; z-index: 10; transition: all 0.2s;"
                    onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                {{-- Contenido principal --}}
                <div style="padding: 40px 32px 24px; text-align: center;">

                    {{-- Icono animado --}}
                    <div
                        style="position: relative; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <span
                            style="position: absolute; width: 96px; height: 96px; border-radius: 50%; background: #34d399; opacity: 0.3; animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;"></span>
                        <span class="success-pop"
                            style="position: relative; display: inline-flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #34d399, #0d9488); color: white; box-shadow: 0 10px 25px -5px rgba(52, 211, 153, 0.5);">
                            <svg width="40" height="40" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                    </div>

                    {{-- Título --}}
                    <h2 style="font-size: 26px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
                        ¡Acceso Correcto!
                    </h2>
                    <p style="font-size: 14px; color: #64748b; margin-bottom: 24px;">
                        Has iniciado sesión exitosamente
                    </p>

                    {{-- Card del usuario --}}
                    <div
                        style="background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            {{-- Avatar --}}
                            <div
                                style="flex-shrink: 0; width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #4f46e5); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);">
                                {{ strtoupper(substr(Auth::user()->nombre_completo, 0, 2)) }}
                            </div>

                            <div style="flex: 1; text-align: left; min-width: 0;">
                                <p
                                    style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
                                    Bienvenido
                                </p>
                                <p
                                    style="font-weight: 800; color: #0f172a; font-size: 16px; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ Auth::user()->nombre_completo }}
                                </p>
                                <div
                                    style="display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap;">
                                    <span
                                        style="display: inline-flex; align-items: center; padding: 3px 8px; background: #dbeafe; color: #1d4ed8; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase;">
                                        {{ Auth::user()->rol }}
                                    </span>
                                    @if (Auth::user()->cargo)
                                        <span
                                            style="font-size: 11px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ Auth::user()->cargo }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Barra de progreso --}}
                <div style="height: 4px; background: #f1f5f9; overflow: hidden;">
                    <div id="welcome-progress"
                        style="height: 100%; background: linear-gradient(90deg, #34d399, #14b8a6); width: 100%;"></div>
                </div>

            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- SCRIPTS --}}
    {{-- ============================================================ --}}
    @yield('scripts')
    @stack('scripts')

    <script>
        // ============================================================
        // ESTILOS DE ANIMACIONES
        // ============================================================
        (function() {
            const style = document.createElement('style');
            style.textContent = `
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            @keyframes ping {
                75%, 100% { transform: scale(2); opacity: 0; }
            }
        `;
            document.head.appendChild(style);
        })();

        // ============================================================
        // MENÚ MÓVIL
        // ============================================================
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }

        document.getElementById('sidebarOverlay')?.addEventListener('click', toggleSidebar);

        // ============================================================
        // SWEET ALERT - Mensajes de sesión
        // ============================================================
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = document.getElementById('theme-toggle-icon');

            function updateThemeButton() {
                const dark = document.documentElement.classList.contains('dark');
                if (themeIcon) themeIcon.textContent = dark ? '☀️' : '🌙';
                if (themeToggle) themeToggle.title = dark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
            }

            updateThemeButton();
            themeToggle?.addEventListener('click', function() {
                const dark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('sisactivos-theme', dark ? 'dark' : 'light');
                updateThemeButton();
            });

            @if (session('status'))
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: @json(session('status')),
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif
            @if (session('success'))
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: @json(session('success')),
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif
            @if (session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: @json(session('error'))
                });
            @endif
            @if (session('warning'))
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: @json(session('warning'))
                });
            @endif
            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Errores de validación',
                    html: `<ul style="text-align:left; font-size:13px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>`
                });
            @endif
        });

        // ============================================================
        // CONFIRMAR ELIMINACIÓN
        // ============================================================
        function confirmDelete(formId) {
            Swal.fire({
                title: '¿Estás seguro de eliminar?',
                text: '¡Esta acción no se puede deshacer!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById(formId);
                    if (form) form.submit();
                }
            });
        }

        // ============================================================
        // LOADING BAR
        // ============================================================
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const bar = document.getElementById('loadingBar');
                if (bar) bar.classList.add('active');
            });
        });

        // ============================================================
        // MODAL DE BIENVENIDA
        // ============================================================
        @if (session('login_success'))
            (function() {
                const modal = document.getElementById('welcome-modal');
                const card = document.getElementById('welcome-card');
                const progress = document.getElementById('welcome-progress');
                const DURACION_MS = 4000;

                if (!modal || !card || !progress) return;

                // Audio de bienvenida
                try {
                    const audio = new Audio("{{ asset('audios/bienvenido.mp3') }}");
                    audio.volume = 0.7;
                    audio.play().catch(e => console.log('Audio bloqueado:', e.message));
                } catch (e) {
                    console.error('Error al reproducir audio:', e);
                }

                // Mostrar modal con animación
                requestAnimationFrame(() => {
                    modal.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                });

                // Animar barra de progreso
                setTimeout(() => {
                    if (progress) {
                        progress.style.width = '0%';
                        progress.style.transition = `width ${DURACION_MS}ms linear`;
                    }
                }, 100);

                // Auto-cerrar
                const timeoutId = setTimeout(() => {
                    cerrarModalBienvenida();
                }, DURACION_MS);

                // Función global para cerrar
                window.cerrarModalBienvenida = function() {
                    clearTimeout(timeoutId);
                    modal.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';

                    setTimeout(() => {
                        modal.remove();
                    }, 300);
                };

                // Cerrar con clic fuera del card
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        cerrarModalBienvenida();
                    }
                });

                // Cerrar con ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        cerrarModalBienvenida();
                    }
                });

                // LIMPIAR EL FLAG DESPUÉS DE MOSTRAR EL MODAL
                setTimeout(() => {
                    fetch('{{ route('login.clear-welcome') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    }).catch(() => {
                        // Silencioso
                    });
                }, 1500);
            })();
        @endif
    </script>

</body>
</html>
