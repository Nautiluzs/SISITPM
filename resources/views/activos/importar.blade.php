@extends('layouts.app')

@section('title', 'Importar Activos desde Excel')

@section('content')
<div class="px-4 py-6 max-w-4xl mx-auto text-slate-800 dark:text-slate-100">

    {{-- ALERTAS DE ÉXITO O ERROR --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 rounded-xl flex items-center gap-3 shadow-sm">
            <span class="text-xl">✅</span>
            <div class="text-sm font-bold">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 rounded-xl shadow-sm">
            <div class="font-bold text-sm mb-1">⚠️ Se encontraron errores en la importación:</div>
            <ul class="list-disc list-inside text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('errores_importacion'))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
            <div class="mb-2 text-sm font-black">Filas omitidas durante la importación:</div>
            <ul class="list-disc space-y-1 pl-5 text-xs">
                @foreach(session('errores_importacion') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORMULARIO DE CARGA --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-8 shadow-sm">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-900 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-200">
            <div>
                <div class="font-black">Usa la plantilla oficial para evitar errores</div>
                <p class="mt-1 text-xs">Incluye los encabezados correctos y un ejemplo de formato.</p>
            </div>
            <a href="{{ route('activos.importar.template') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-black text-white no-underline hover:bg-blue-700">
                Descargar plantilla Excel
            </a>
        </div>

        <div class="mb-6 grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs dark:border-slate-700 dark:bg-slate-800">
            <div class="font-black text-slate-800 dark:text-white">IDs disponibles para llenar el Excel</div>
            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <div class="mb-1 font-black text-slate-700 dark:text-slate-200">Carreras</div>
                    @foreach($carreras as $carrera)
                        <div class="text-slate-600 dark:text-slate-300">{{ $carrera->id_carrera }} - {{ $carrera->nombre }}</div>
                    @endforeach
                </div>
                <div>
                    <div class="mb-1 font-black text-slate-700 dark:text-slate-200">Ambientes</div>
                    @foreach($ambientes as $ambiente)
                        <div class="text-slate-600 dark:text-slate-300">{{ $ambiente->id_ambiente }} - {{ $ambiente->nombre }} (Carrera {{ $ambiente->id_carrera }})</div>
                    @endforeach
                </div>
                <div>
                    <div class="mb-1 font-black text-slate-700 dark:text-slate-200">Categorías</div>
                    @foreach($categorias as $categoria)
                        <div class="text-slate-600 dark:text-slate-300">{{ $categoria->id_categoria }} - {{ $categoria->nombre }} (Ambiente {{ $categoria->id_ambiente }})</div>
                    @endforeach
                </div>
            </div>
        </div>

        <form action="{{ route('activos.importar.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
            @csrf

            <div>
                <label class="block text-xs font-black text-slate-600 dark:text-slate-400 mb-2 uppercase tracking-wide">Seleccionar archivo Excel o CSV</label>
                <input type="file" name="archivo_excel" accept=".xlsx,.xls,.csv,.txt" required
                       class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-sm font-semibold text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer transition-colors">
                <p class="text-xs text-slate-500 mt-2">Puedes cargar archivos XLSX, XLS o CSV. La primera fila debe contener los nombres de las columnas.</p>
            </div>

            <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 p-4 rounded-xl text-xs text-blue-800 dark:text-blue-300 space-y-2">
                <div class="font-bold">📋 Estructura obligatoria de columnas en la primera fila:</div>
                <code class="block bg-white dark:bg-slate-900 p-2 rounded border border-blue-200 dark:border-blue-800 text-[11px]">codigo_activo, tipo_bien, nombre, descripcion, marca, modelo, numero_serie, id_ambiente, id_categoria, id_fuente, id_custodio, estado_fisico, rv, fecha_adquisicion, valor_adquisicion</code>
                <div>En <strong>rv</strong> escribe <strong>SI</strong> o <strong>NO</strong>. También se reconocen encabezados antiguos como VISAF/VSIAF y se guardan como RV.</div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('activos.index') }}" class="px-5 py-3 bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-bold text-sm transition-colors no-underline">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-sm shadow-md transition-all cursor-pointer">
                    🚀 Procesar e Importar Activos
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
