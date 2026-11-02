@extends('layouts.app')

@section('title', 'Manual del Sistema')

@section('content')
<div class="px-4 py-8 max-w-7xl mx-auto text-slate-800 dark:text-slate-100 transition-colors duration-200">

    {{-- ENCABEZADO CORPORATIVO --}}
    <div class="bg-linear-to-r from-slate-900 via-teal-900 to-slate-900 dark:from-slate-950 dark:to-teal-950 p-8 rounded-2xl text-white mb-10 shadow-xl border border-slate-800">
        <span class="bg-white/15 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider">Centro de Ayuda y Guía Rápida</span>
        <h1 class="text-3xl font-black mt-3 mb-2 tracking-tight text-white">Manual Interactivo — SISActivos</h1>
        <p class="text-slate-300 text-sm m-0 leading-relaxed max-w-3xl">Guía de consulta para organizar el sistema, registrar activos y realizar verificaciones físicas con información clara, ordenada y fácil de revisar.</p>
    </div>

    <nav aria-label="Contenido del manual" class="mb-12 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-4">
            <span class="text-xs font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-400">Guía de navegación</span>
            <h2 class="mt-1 text-lg font-extrabold text-slate-900 dark:text-white">¿Qué necesitas hacer?</h2>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <a href="#acceso" class="rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">🔐 Acceder y conocer los roles</a>
            <a href="#usuarios-rector" class="rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">👥 Actualización de Datos al Personal Administrativo</a>
            <a href="#tema" class="rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">🌙 Cambiar el tema</a>
            <a href="#preparacion" class="rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-700 transition hover:border-teal-300 hover:bg-teal-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">📋 Preparar el activo</a>
            <a href="#registro-detallado" class="rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-700 transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">📦 Registrar activos</a>
            <a href="#verificacion" class="rounded-xl border border-slate-200 p-4 text-sm font-bold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">✅ Verificar y cerrar</a>
        </div>
    </nav>

    {{-- 🚀 SECCIÓN 1: PRIMEROS PASOS (ACCESO AL SISTEMA) --}}
    <section id="acceso" class="mb-12 scroll-mt-24">
        <div class="flex items-center gap-3 mb-6">
            <span class="text-2xl p-2 bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 rounded-xl">🔐</span>
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white m-0">Paso 1: Acceso al Sistema y Credenciales</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-slate-900 p-7 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="inline-block bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 font-extrabold text-xs px-3 py-1 rounded-lg mb-3">ACCESO INICIAL</span>
                    <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2">1. Iniciar sesión</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Abre la dirección institucional del sistema e ingresa con el identificador y la contraseña que te proporcionó el administrador. Comprueba que estás usando tu propia cuenta antes de continuar.</p>
                </div>
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
                    <strong>Importante:</strong> no compartas contraseñas ni uses credenciales de ejemplo. Si no puedes ingresar, solicita ayuda al administrador.
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 p-7 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <span class="inline-block bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 font-extrabold text-xs px-3 py-1 rounded-lg mb-3">ROLES Y PERMISOS</span>
                <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2">2. Conoce tu Rol</h3>
                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 list-disc list-inside">
                    <li><strong>Administrador:</strong> configura catálogos, gestiona cuentas y revisa aprobaciones.</li>
                    <li><strong>Inventariador:</strong> realiza las verificaciones que tiene asignadas.</li>
                    <li><strong>Ayudante:</strong> colabora en las tareas operativas habilitadas para su cuenta.</li>
                    <li><strong>Jefe/Rector:</strong> consulta la información disponible según sus permisos.</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-slate-900 p-7 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <span class="inline-block bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 font-extrabold text-xs px-3 py-1 rounded-lg mb-3">PANEL GENERAL</span>
                <h3 class="font-bold text-slate-900 dark:text-white text-base mb-2">3. Interfaz y Tema</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Usa el botón de tema en la barra superior para alternar entre el Modo Claro y el Modo Oscuro.</p>
            </div>
        </div>
    </section>

    <section id="usuarios-rector" class="mb-12 scroll-mt-24">
        <div class="mb-6 flex items-center gap-3">
            <span class="rounded-xl bg-indigo-100 p-2 text-2xl text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300" aria-hidden="true">👥</span>
            <div>
                <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Administración de cuentas</span>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">El Rector se actualiza únicamente desde Usuarios</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="mb-5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    El nombre, cargo y rol del Rector pertenecen a su cuenta de usuario. La gestión de cuentas está disponible para el administrador.
                </p>
                <ol class="space-y-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">1</span><span>Entra a <strong class="text-slate-900 dark:text-white">Usuarios</strong> y busca la cuenta actual del Rector.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">2</span><span>Abre <strong class="text-slate-900 dark:text-white">Editar</strong> para modificar el nombre completo o el cargo de esa cuenta.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">3</span><span>Conserva el rol <strong class="text-slate-900 dark:text-white">Rector</strong> si solo estás actualizando sus datos; cambiar el rol también cambia sus permisos.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">4</span><span>Guarda los cambios y revisa que el nombre y el rol se muestren correctamente.</span></li>
                </ol>
            </article>

            <aside class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6 dark:border-indigo-900/60 dark:bg-indigo-950/30">
                <h3 class="mb-2 font-extrabold text-indigo-950 dark:text-indigo-200">¿No tienes acceso a Usuarios?</h3>
                <p class="text-sm leading-relaxed text-indigo-900 dark:text-indigo-100">Solicita al administrador que actualice la cuenta. No uses las credenciales de otra persona ni compartas contraseñas por mensajes públicos.</p>
                @if (auth()->user()?->esAdmin())
                    <a href="{{ route('usuarios.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-600">Abrir Usuarios <span aria-hidden="true">→</span></a>
                @endif
            </aside>
        </div>
    </section>

    <section id="tema" class="mb-12 scroll-mt-24">
        <div class="mb-6 flex items-center gap-3">
            <span class="rounded-xl bg-slate-200 p-2 text-2xl text-slate-700 dark:bg-slate-800 dark:text-slate-200" aria-hidden="true">🌙</span>
            <div>
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400">Apariencia del sistema</span>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">El modo oscuro se aplica en todo SISActivos</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 font-black text-blue-700 dark:bg-blue-950 dark:text-blue-300">1</span>
                <h3 class="mb-2 font-extrabold text-slate-900 dark:text-white">Cambia el tema desde cualquier página</h3>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">Usa el botón <strong>Modo oscuro / Modo claro</strong> de la barra superior. El control está disponible en los módulos del sistema.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 font-black text-violet-700 dark:bg-violet-950 dark:text-violet-300">2</span>
                <h3 class="mb-2 font-extrabold text-slate-900 dark:text-white">El tema se guarda en tu navegador</h3>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">Elige <strong>Oscuro</strong> o <strong>Claro</strong> con el botón de la barra superior. La selección se guarda automáticamente en este navegador.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">3</span>
                <h3 class="mb-2 font-extrabold text-slate-900 dark:text-white">Tu preferencia no cambia los datos</h3>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">El modo modifica solo la apariencia de esta instalación en tu navegador. No cambia usuarios, permisos, activos ni el tema que eligieron otras personas.</p>
            </article>
        </div>
    </section>

    {{-- 📋 SECCIÓN 2: ORDEN LÓGICO DE REGISTRO (ESTRUCTURA INSTITUCIONAL) --}}
    <section id="preparacion" class="mb-12 scroll-mt-24">
        <div class="flex items-center gap-3 mb-6">
            <span class="text-2xl p-2 bg-teal-100 dark:bg-teal-950 text-teal-600 dark:text-teal-400 rounded-xl">📋</span>
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white m-0">Paso 2: Orden Lógico de Registro (Estructura Institucional)</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">

            <!-- Paso 01 -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-4 right-4 text-3xl font-black text-slate-100 dark:text-slate-800 select-none">01</div>
                <div>
                    <span class="text-3xl block mb-3">👤</span>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-2">Registrar Usuario</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Crea al personal, asígnalos a su cargo (ej. Rector, Inventariador) y define sus credenciales.</p>
                </div>
            </div>

            <!-- Paso 02 -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-4 right-4 text-3xl font-black text-slate-100 dark:text-slate-800 select-none">02</div>
                <div>
                    <span class="text-3xl block mb-3">🎓</span>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-2">Crear Carrera</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Registra las áreas académicas o facultades institucionales (ej. Sistemas Informáticos).</p>
                </div>
            </div>

            <!-- Paso 03 -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-4 right-4 text-3xl font-black text-slate-100 dark:text-slate-800 select-none">03</div>
                <div>
                    <span class="text-3xl block mb-3">🏢</span>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-2">Definir Ambiente</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Añade los espacios físicos específicos, aulas o laboratorios vinculados a la carrera.</p>
                </div>
            </div>

            <!-- Paso 04 -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-4 right-4 text-3xl font-black text-slate-100 dark:text-slate-800 select-none">04</div>
                <div>
                    <span class="text-3xl block mb-3">📁</span>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-2">Crear Categoría</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Agrupa el inventario por tipo dentro del ambiente (ej. Equipos de Computación, Mobiliario).</p>
                </div>
            </div>

            <!-- Paso 05 -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden flex flex-col justify-between sm:col-span-2 lg:col-span-1">
                <div class="absolute top-4 right-4 text-3xl font-black text-slate-100 dark:text-slate-800 select-none">05</div>
                <div>
                    <span class="text-3xl block mb-3">📦</span>
                    <h3 class="font-bold text-slate-900 dark:text-white text-sm mb-2">Registrar Activo</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Ingresa el bien (Activo Fijo o No Activo), adjunta sus fotos y genera su QR y etiqueta listos para imprimir.</p>
                </div>
            </div>

        </div>
        <div class="mt-8 rounded-2xl border border-teal-200 bg-teal-50 p-6 dark:border-teal-900/60 dark:bg-teal-950/30">
            <h3 class="mb-2 text-base font-extrabold text-teal-950 dark:text-teal-200">Antes de registrar información</h3>
            <p class="mb-4 text-sm leading-relaxed text-teal-900 dark:text-teal-100">Acordar nombres y criterios entre las personas que cargarán datos ayuda a evitar duplicados y facilita las búsquedas y los reportes.</p>
            <ul class="grid grid-cols-1 gap-3 text-sm text-teal-900 dark:text-teal-100 md:grid-cols-2">
                <li class="flex gap-2"><span aria-hidden="true">✓</span><span>Reúne los nombres oficiales de carreras y ambientes.</span></li>
                <li class="flex gap-2"><span aria-hidden="true">✓</span><span>Define categorías coherentes para bienes del mismo tipo.</span></li>
                <li class="flex gap-2"><span aria-hidden="true">✓</span><span>Verifica con el responsable los datos disponibles de cada activo.</span></li>
                <li class="flex gap-2"><span aria-hidden="true">✓</span><span>Asigna únicamente los permisos necesarios a cada cuenta.</span></li>
            </ul>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">Secuencia recomendada</span>
                <h3 class="mb-4 mt-1 text-lg font-extrabold text-slate-900 dark:text-white">Construye la estructura de arriba hacia abajo</h3>
                <ol class="space-y-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-black text-blue-700 dark:bg-blue-950 dark:text-blue-300">1</span><span><strong class="text-slate-900 dark:text-white">Carreras:</strong> revisa que el nombre identifique correctamente el área institucional.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-black text-blue-700 dark:bg-blue-950 dark:text-blue-300">2</span><span><strong class="text-slate-900 dark:text-white">Ambientes:</strong> asocia cada espacio a la carrera que corresponde y usa una denominación reconocible.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-black text-blue-700 dark:bg-blue-950 dark:text-blue-300">3</span><span><strong class="text-slate-900 dark:text-white">Categorías:</strong> agrupa bienes con características semejantes para facilitar su clasificación.</span></li>
                    <li class="flex gap-3"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-black text-blue-700 dark:bg-blue-950 dark:text-blue-300">4</span><span><strong class="text-slate-900 dark:text-white">Activos:</strong> registra cada bien y verifica su ubicación y clasificación antes de guardar.</span></li>
                </ol>
                <a href="{{ route('carreras.index') }}" class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-blue-700 hover:underline dark:text-blue-300">Ir a Carreras <span aria-hidden="true">→</span></a>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="text-xs font-extrabold uppercase tracking-wider text-violet-600 dark:text-violet-400">Control de calidad</span>
                <h3 class="mb-4 mt-1 text-lg font-extrabold text-slate-900 dark:text-white">Comprueba cada registro antes de continuar</h3>
                <ul class="space-y-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    <li class="flex gap-3"><span class="text-emerald-600 dark:text-emerald-400">●</span><span>Busca si ya existe una carrera, ambiente o categoría equivalente.</span></li>
                    <li class="flex gap-3"><span class="text-emerald-600 dark:text-emerald-400">●</span><span>Confirma que la relación entre carrera y ambiente sea correcta.</span></li>
                    <li class="flex gap-3"><span class="text-emerald-600 dark:text-emerald-400">●</span><span>Evita abreviaturas ambiguas y nombres distintos para el mismo concepto.</span></li>
                    <li class="flex gap-3"><span class="text-emerald-600 dark:text-emerald-400">●</span><span>Si detectas un dato incorrecto, coordina su corrección con quien tenga el permiso correspondiente.</span></li>
                </ul>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('categorias.index') }}" class="rounded-lg bg-violet-50 px-4 py-2 text-sm font-bold text-violet-800 hover:bg-violet-100 dark:bg-violet-950/60 dark:text-violet-200">Ver categorías</a>
                    <a href="{{ route('activos.index') }}" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-bold text-slate-800 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200">Consultar activos</a>
                </div>
            </article>
        </div>
    </section>

    <section id="registro-detallado" class="mb-12 scroll-mt-24">
        <div class="mb-6 flex items-center gap-3">
            <span class="rounded-xl bg-violet-100 p-2 text-2xl text-violet-700 dark:bg-violet-950 dark:text-violet-300" aria-hidden="true">📦</span>
            <div>
                <span class="text-xs font-extrabold uppercase tracking-wider text-violet-600 dark:text-violet-400">Procedimiento operativo</span>
                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white">Registro y revisión de activos</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-blue-100 font-black text-blue-700 dark:bg-blue-950 dark:text-blue-300">01</span>
                <h3 class="mb-2 font-extrabold text-slate-900 dark:text-white">Identifica el bien</h3>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">Reúne los datos que permitan distinguirlo de otros: denominación, código o número identificador disponible, características y condición observada. No completes datos por suposición.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 font-black text-teal-700 dark:bg-teal-950 dark:text-teal-300">02</span>
                <h3 class="mb-2 font-extrabold text-slate-900 dark:text-white">Clasifica y ubica</h3>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">Selecciona la categoría, carrera y ambiente que correspondan. Revisa la ubicación física con la persona responsable antes de guardar el registro.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="mb-3 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 font-black text-amber-700 dark:bg-amber-950 dark:text-amber-300">03</span>
                <h3 class="mb-2 font-extrabold text-slate-900 dark:text-white">Revisa y conserva</h3>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">Verifica los campos y las imágenes antes de confirmar. Después, consulta el registro y utiliza la etiqueta o el código QR asociado cuando esté disponible.</p>
            </article>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="mb-4 text-base font-extrabold text-slate-900 dark:text-white">Lista de revisión del registro</h3>
                <ul class="grid grid-cols-1 gap-3 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2">
                    <li class="flex gap-2"><span class="text-emerald-600">✓</span>La descripción corresponde al bien observado.</li>
                    <li class="flex gap-2"><span class="text-emerald-600">✓</span>La categoría y el ambiente son los correctos.</li>
                    <li class="flex gap-2"><span class="text-emerald-600">✓</span>Los datos identificadores se transcribieron con cuidado.</li>
                    <li class="flex gap-2"><span class="text-emerald-600">✓</span>Las imágenes seleccionadas son legibles y pertinentes.</li>
                    <li class="flex gap-2"><span class="text-emerald-600">✓</span>Se revisó si ya había un registro para evitar duplicados.</li>
                    <li class="flex gap-2"><span class="text-emerald-600">✓</span>Se confirmó el resultado después de guardar.</li>
                </ul>
            </article>
            <aside class="rounded-2xl border border-blue-200 bg-blue-50 p-6 dark:border-blue-900/60 dark:bg-blue-950/30">
                <h3 class="mb-2 font-extrabold text-blue-950 dark:text-blue-200">Etiquetas y códigos QR</h3>
                <p class="text-sm leading-relaxed text-blue-900 dark:text-blue-100">Imprime la etiqueta que corresponda al registro y colócala en un lugar visible sin cubrir placas, números de serie ni información del fabricante. Si el código no se puede leer, revisa su impresión y confirma el activo desde el sistema.</p>
                <a href="{{ route('activos.index') }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-blue-800 hover:underline dark:text-blue-200">Abrir inventario <span aria-hidden="true">→</span></a>
            </aside>
        </div>
    </section>

    {{-- ⚡ SECCIÓN 3: CICLO DE VERIFICACIÓN FÍSICA --}}
    <section id="verificacion" class="mb-12 scroll-mt-24 rounded-2xl border border-slate-700 bg-linear-to-br from-slate-800 to-slate-900 p-8 text-white shadow-xl dark:from-slate-900 dark:to-slate-950">
        <div class="flex items-center gap-3 mb-6">
            <span class="text-2xl p-2 bg-amber-500/20 text-amber-400 rounded-xl">⚡</span>
            <div>
                <span class="text-xs font-extrabold uppercase tracking-wider text-teal-300">Paso 4 · Inventario en campo</span>
                <h2 class="text-xl font-black text-white">Verificación física, revisión y cierre</h2>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <article class="rounded-xl border border-white/10 bg-white/10 p-6">
                <span class="mb-3 inline-flex rounded-lg bg-teal-400/15 px-3 py-1 text-xs font-extrabold text-teal-200">FASE 1 · PREPARACIÓN</span>
                <h3 class="mb-3 font-extrabold text-white">Antes de recorrer el ambiente</h3>
                <ol class="list-inside list-decimal space-y-2 text-sm leading-relaxed text-slate-200">
                    <li>Confirma tu usuario y las opciones de verificación disponibles.</li>
                    <li>Comprueba que estás en la carrera y el ambiente asignados.</li>
                    <li>Ten a mano el dispositivo y revisa que puedas acceder al sistema.</li>
                    <li>Coordina el recorrido con el responsable del espacio.</li>
                </ol>
            </article>

            <article class="rounded-xl border border-white/10 bg-white/10 p-6">
                <span class="mb-3 inline-flex rounded-lg bg-amber-400/15 px-3 py-1 text-xs font-extrabold text-amber-200">FASE 2 · REVISIÓN</span>
                <h3 class="mb-3 font-extrabold text-white">Durante el recorrido</h3>
                <ol class="list-inside list-decimal space-y-2 text-sm leading-relaxed text-slate-200">
                    <li>Localiza cada bien y coteja su identificación con el registro.</li>
                    <li>Usa el QR si está disponible; si no, busca el activo en el sistema.</li>
                    <li>Revisa la ubicación y los datos visibles antes de marcar el resultado.</li>
                    <li>Registra las diferencias según las opciones disponibles y evita duplicar lecturas.</li>
                </ol>
            </article>

            <article class="rounded-xl border border-white/10 bg-white/10 p-6">
                <span class="mb-3 inline-flex rounded-lg bg-violet-400/15 px-3 py-1 text-xs font-extrabold text-violet-200">FASE 3 · CIERRE</span>
                <h3 class="mb-3 font-extrabold text-white">Al terminar el ambiente</h3>
                <ol class="list-inside list-decimal space-y-2 text-sm leading-relaxed text-slate-200">
                    <li>Repasa los resultados y comprueba que no queden bienes sin revisar por error.</li>
                    <li>Finaliza la verificación solo cuando el recorrido esté completo.</li>
                    <li>Informa al responsable si hubo diferencias o dificultades de acceso.</li>
                    <li>Consulta el reporte o estado posterior según tus permisos.</li>
                </ol>
            </article>
        </div>

        <div class="mt-6 rounded-xl border border-amber-300/30 bg-amber-300/10 p-5">
            <h3 class="mb-2 font-extrabold text-amber-100">¿Encontraste una diferencia?</h3>
            <p class="text-sm leading-relaxed text-slate-200">No cambies la ubicación ni elimines un registro para hacer coincidir los datos sin autorización. Anota la información observada y notifícala al responsable para que pueda revisarla y decidir la corrección adecuada.</p>
        </div>

        <a href="{{ route('verificaciones.index') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-teal-400 px-5 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-teal-300">Ir a Verificaciones <span aria-hidden="true">→</span></a>
    </section>

    <section class="mb-12">
        <div class="mb-6">
            <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600 dark:text-blue-400">Resolución de problemas</span>
            <h2 class="mt-1 text-xl font-extrabold text-slate-900 dark:text-white">Preguntas frecuentes</h2>
        </div>
        <div class="space-y-3">
            <details class="group rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <summary class="cursor-pointer list-none pr-6 font-bold text-slate-900 marker:hidden dark:text-white">No puedo iniciar sesión. ¿Qué debo revisar?<span class="float-right text-blue-600 transition group-open:rotate-45 dark:text-blue-400">＋</span></summary>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Verifica que el identificador y la contraseña sean los asignados a tu cuenta, revisa que el teclado no tenga activadas mayúsculas y confirma tu conexión. Si el problema continúa, solicita al administrador que revise el estado de la cuenta. No envíes tu contraseña por un canal público.</p>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <summary class="cursor-pointer list-none pr-6 font-bold text-slate-900 marker:hidden dark:text-white">No encuentro una carrera, ambiente o categoría.<span class="float-right text-blue-600 transition group-open:rotate-45 dark:text-blue-400">＋</span></summary>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Comprueba los filtros y los términos de búsqueda. Puede que el elemento todavía no esté registrado o que tu cuenta no tenga permiso para administrarlo. Consulta con el administrador antes de crear un duplicado.</p>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <summary class="cursor-pointer list-none pr-6 font-bold text-slate-900 marker:hidden dark:text-white">El código QR no se lee o el activo no aparece.<span class="float-right text-blue-600 transition group-open:rotate-45 dark:text-blue-400">＋</span></summary>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Mejora la iluminación, limpia la cámara y centra el código. Si no funciona, busca el activo por sus datos identificadores en el inventario. Antes de solicitar una nueva etiqueta, confirma que estás consultando el ambiente correcto.</p>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <summary class="cursor-pointer list-none pr-6 font-bold text-slate-900 marker:hidden dark:text-white">No veo una opción o botón en el menú.<span class="float-right text-blue-600 transition group-open:rotate-45 dark:text-blue-400">＋</span></summary>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-300">Las opciones disponibles dependen del rol y los permisos de tu cuenta. Si consideras que necesitas una función para tu trabajo, pide al administrador que confirme tus permisos; no utilices la cuenta de otra persona.</p>
            </details>
        </div>
    </section>

    <footer class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">Un inventario confiable empieza con datos verificables</h2>
        <p class="mx-auto mt-2 max-w-2xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">Registra lo que observas, revisa antes de guardar y comunica las diferencias. Si una acción no está disponible, consulta con la persona administradora del sistema.</p>
        <a href="{{ route('dashboard') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white transition hover:bg-slate-700 dark:bg-teal-500 dark:text-slate-950 dark:hover:bg-teal-400">Volver al panel <span aria-hidden="true">→</span></a>
    </footer>
</div>
@endsection
