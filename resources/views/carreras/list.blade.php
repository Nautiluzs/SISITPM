@extends('layouts.app')

@section('title', 'Gestión de Carreras')

@section('content')

<div class="carreras-page px-4 py-6 max-w-7xl mx-auto text-slate-800 dark:text-slate-100 transition-colors duration-200">

    {{-- ENCABEZADO CORPORATIVO --}}
    <div class="bg-gradient-to-r from-slate-900 via-teal-900 to-slate-900
                dark:from-slate-950 dark:via-teal-950 dark:to-slate-950
                p-8 rounded-2xl text-white mb-8 shadow-xl border border-slate-700
                flex flex-col md:flex-row justify-between items-start md:items-center gap-6">

        <div>
            <span class="bg-teal-600 text-white px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider shadow-sm">
                Estructura Académica
            </span>

            <h1 class="text-3xl font-black mt-3 mb-1 tracking-tight text-white">
                Carreras Institucionales
            </h1>

            {{-- TEXTO BLANCO EN AMBOS MODOS --}}
            <p class="text-white text-sm m-0">
                Listado general de carreras, áreas académicas y asignación de jefaturas.
            </p>
        </div>

        {{-- TOTAL DE CARRERAS --}}
        <div class="flex items-center gap-4 bg-slate-800 dark:bg-slate-900 px-5 py-3 rounded-xl border border-slate-600 shadow-lg">
            <div class="text-right">
                <span class="text-xs text-slate-300 block font-semibold">
                    Total Registradas
                </span>

                <span class="text-2xl font-black text-white">
                    {{ $carreras->count() }}
                </span>
            </div>

            @if(auth()->check() && auth()->user()->esAdmin())
                <a href="{{ route('carreras.create') }}"
                   class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-4 py-2.5 rounded-xl text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer no-underline">
                    <span>+</span> Nueva Carrera
                </a>
            @endif
        </div>
    </div>

    {{-- ALERTA DE ESTADO --}}
    @if(session('status'))
        <div class="mb-6 p-4 bg-blue-100 dark:bg-blue-950 border border-blue-300 dark:border-blue-700
                    text-blue-900 dark:text-blue-100 rounded-xl flex items-center gap-3 shadow-sm">
            <span class="text-xl">ℹ️</span>
            <div class="text-sm font-bold">{{ session('status') }}</div>
        </div>
    @endif

    {{-- ALERTA DE ÉXITO --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-100 dark:bg-emerald-950 border border-emerald-300 dark:border-emerald-700
                    text-emerald-900 dark:text-emerald-100 rounded-xl flex items-center gap-3 shadow-sm">
            <span class="text-xl">✅</span>
            <div class="text-sm font-bold">{{ session('success') }}</div>
        </div>
    @endif

    {{-- ERRORES DE VALIDACIÓN --}}
    @if($errors->any())
        <div class="mb-6 p-4 bg-rose-100 dark:bg-rose-950 border border-rose-300 dark:border-rose-700
                    text-rose-900 dark:text-rose-100 rounded-xl shadow-sm">

            <div class="font-bold text-sm mb-1">
                ⚠️ Se encontraron algunos errores:
            </div>

            <ul class="list-disc list-inside text-xs space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- CONTENEDOR PRINCIPAL --}}
    <div class="bg-slate-50 dark:bg-slate-900 rounded-2xl border border-slate-300 dark:border-slate-700
                p-6 shadow-md transition-colors duration-200">

        {{-- ENCABEZADO DEL LISTADO --}}
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3
                    mb-6 border-b border-slate-300 dark:border-slate-700 pb-4">

            <h2 class="text-lg font-black text-slate-900 dark:text-white m-0 flex items-center gap-2">
                <span>📂</span>
                Listado de Carreras y Ambientes
            </h2>

            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300
                         bg-white dark:bg-slate-800 px-3 py-1 rounded-lg
                         border border-slate-200 dark:border-slate-600">
                Usa el botón Ingresar para abrir
            </span>
        </div>

        {{-- GRID DE TARJETAS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

            @foreach($carreras as $c)

                <div class="carrera-card relative bg-white dark:bg-slate-800
                            border-2 border-slate-200 dark:border-slate-600
                            hover:border-blue-500 dark:hover:border-blue-400
                            rounded-2xl p-5 text-left transition-all duration-200
                            hover:-translate-y-1 hover:shadow-xl
                            flex flex-col justify-between">

                    {{-- ID DE CARRERA --}}
                    <span class="absolute top-3 left-3 text-lg font-black text-slate-900 dark:text-white">
                        {{ $c->id_carrera }}
                    </span>

                    <div class="carrera-card-content">

                        {{-- ICONO Y ESTADO --}}
                        <div class="flex justify-between items-start mb-4 pt-6">

                            <span class="text-3xl p-2 bg-slate-100 dark:bg-slate-700
                                         rounded-xl shadow-sm border border-slate-200 dark:border-slate-600">
                                🎓
                            </span>

                            <span class="text-[10px] font-black uppercase px-2.5 py-1
                                         bg-blue-600 text-white rounded-lg shadow-sm">
                                Activa
                            </span>
                        </div>

                        {{-- NOMBRE DE CARRERA --}}
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mb-4 leading-tight">
                            {{ $c->nombre }}
                        </h3>

                        {{-- JEFE DE CARRERA --}}
                        <div class="bg-slate-50 dark:bg-slate-900 p-3 rounded-xl
                                    border border-slate-200 dark:border-slate-600 mb-4 shadow-inner">

                            <span class="text-[10px] uppercase tracking-wider font-extrabold
                                         text-slate-500 dark:text-slate-400 block mb-1">
                                👤 Jefe de Carrera
                            </span>

                            <span class="text-sm font-bold text-slate-800 dark:text-slate-200 block truncate">
                                {{ $c->jefeCarrera?->nombre_completo ?? 'No asignado' }}
                            </span>
                        </div>

                        {{-- AMBIENTES --}}
                        <div class="mb-4">

                            <span class="mb-2 block text-[10px] font-extrabold uppercase tracking-wider
                                         text-slate-500 dark:text-slate-400">
                                Ambientes
                            </span>

                            @forelse($c->ambientes as $ambiente)

                                <a href="{{ route('ambientes.show', $ambiente->id_ambiente) }}"
                                   class="mb-1 flex items-center justify-between rounded-lg
                                          border border-slate-200 dark:border-slate-600
                                          bg-white dark:bg-slate-700 px-2.5 py-2 text-xs font-bold
                                          text-slate-700 dark:text-slate-200
                                          no-underline transition hover:border-blue-400">

                                    <span class="text-slate-700 dark:text-slate-200">
                                        {{ $ambiente->nombre }}
                                    </span>

                                    <span class="text-sm font-black text-slate-900 dark:text-white">
                                        {{ $ambiente->id_ambiente }}
                                    </span>
                                </a>

                            @empty

                                <span class="text-xs italic text-slate-500 dark:text-slate-400">
                                    Sin ambientes registrados
                                </span>

                            @endforelse

                        </div>
                    </div>

                    {{-- BOTONES DE ACCIÓN --}}
                    <div class="flex flex-wrap gap-2 pt-4 border-t border-slate-200 dark:border-slate-600"
                         onclick="event.stopPropagation();">

                        {{-- INGRESAR --}}
                        <a href="{{ route('carreras.show', $c->id_carrera) }}"
                           class="flex-1 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600
                                  text-white text-center font-bold text-xs py-2.5 rounded-xl
                                  transition-colors no-underline shadow-sm">
                            Ingresar
                        </a>

                        {{-- NUEVO AMBIENTE --}}
                        <a href="{{ route('carreras.show', $c->id_carrera) }}?nuevo_ambiente=1"
                           class="flex-1 bg-teal-600 hover:bg-teal-500
                                  text-white text-center font-bold text-xs py-2.5 rounded-xl
                                  transition-colors no-underline shadow-sm"
                           title="Crear nuevo ambiente">
                            + Ambiente
                        </a>

                        @if(auth()->check() && auth()->user()->esAdmin())

                            {{-- EDITAR CARRERA --}}
                            <a href="{{ route('carreras.edit', $c->id_carrera) }}"
                               class="w-full bg-amber-500 hover:bg-amber-400
                                      text-slate-900 text-center font-bold text-xs py-2.5 rounded-xl
                                      transition-colors no-underline shadow-sm mt-1">
                                ✏️ Editar Carrera
                            </a>

                            {{-- ELIMINAR CARRERA --}}
                            <form action="{{ route('carreras.destroy', $c->id_carrera) }}"
                                  method="POST"
                                  class="w-full mt-1"
                                  onsubmit="return confirm('¿Está seguro de eliminar esta carrera? Esta acción no se puede deshacer.');">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="w-full bg-rose-600 hover:bg-rose-500 text-white
                                               text-center font-bold text-xs py-2.5 rounded-xl
                                               transition-colors cursor-pointer border-none shadow-sm">
                                    🗑 Eliminar
                                </button>
                            </form>

                        @endif

                    </div>
                </div>

            @endforeach

        </div>
    </div>
</div>

@endsection
