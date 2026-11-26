@extends('layouts.app')

@section('title', 'Importar activos desde Excel')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-6 text-slate-800 dark:text-slate-100">
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 dark:border-rose-800 dark:bg-rose-950/60 dark:text-rose-200">
            <div class="mb-1 text-sm font-bold">Se encontraron errores:</div>
            <ul class="list-inside list-disc space-y-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('errores_importacion'))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
            <div class="mb-2 text-sm font-black">Filas omitidas durante la importación:</div>
            <ul class="list-disc space-y-1 pl-5 text-xs">
                @foreach (session('errores_importacion') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-900 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-200">
            <div>
                <h1 class="font-black">Importar activos desde Excel</h1>
                <p class="mt-1 text-xs">Descarga la plantilla: la hoja 2 incluye el manual y los IDs actualizados por carrera.</p>
            </div>
            <a href="{{ route('activos.importar.template') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-black text-white no-underline hover:bg-blue-700">
                Descargar plantilla Excel
            </a>
        </header>

        <section class="mb-6 space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
            <h2 class="font-black">Pasos rápidos</h2>
            <ol class="list-inside list-decimal space-y-2">
                <li>En la hoja <strong>Activos</strong>, completa una fila por bien y conserva los encabezados.</li>
                <li>En <strong>Manual e IDs</strong>, busca los IDs de ambientes por carrera, el ID de categoría y el CI del custodio.</li>
                <li>Usa <strong>B</strong> para Bueno, <strong>R</strong> para Regular, <strong>M</strong> para Malo y <strong>FF</strong> para Fuera de funcionamiento.</li>
                <li>Guarda como .xlsx, vuelve a esta página y carga el archivo. Reemplaza o elimina la fila de ejemplo.</li>
            </ol>
        </section>

        <form action="{{ route('activos.importar.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
            @csrf
            <div>
                <label for="archivo_excel" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">Seleccionar archivo Excel o CSV</label>
                <input id="archivo_excel" type="file" name="archivo_excel" accept=".xlsx,.xls,.csv,.txt" required
                    class="w-full cursor-pointer rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 transition-colors file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                <p class="mt-2 text-xs text-slate-500">La plantilla .xlsx incluye la hoja de instrucciones y catálogos; un CSV contiene solo una hoja.</p>
            </div>

            <section class="space-y-2 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-800 dark:border-blue-800/60 dark:bg-blue-950/40 dark:text-blue-300">
                <h2 class="font-bold">Encabezados de la hoja Activos</h2>
                <code class="block overflow-x-auto rounded border border-blue-200 bg-white p-2 text-[11px] dark:border-blue-800 dark:bg-slate-900">codigo_activo, tipo_bien, nombre, descripcion, marca, modelo, numero_serie, id_ambiente, id_categoria, id_fuente, ci_custodio, estado_fisico, rv, fecha_adquisicion, valor_adquisicion</code>
                <p>En <strong>rv</strong> escribe SI o NO. También se reconocen encabezados antiguos como VISAF/VSIAF.</p>
            </section>

            <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <a href="{{ route('activos.index') }}" class="rounded-xl bg-slate-200 px-5 py-3 text-sm font-bold text-slate-700 no-underline transition-colors dark:bg-slate-800 dark:text-slate-300">Cancelar</a>
                <button type="submit" class="cursor-pointer rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-md transition-all hover:bg-blue-700">Procesar e importar activos</button>
            </div>
        </form>
    </div>
</div>
@endsection
