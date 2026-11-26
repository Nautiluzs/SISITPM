@extends('layouts.app')

@section('title', 'Observaciones del acta de recepción')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-8 text-slate-800 dark:text-slate-100">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">Observaciones del acta de recepción</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Escribe la observación que aparecerá en el PDF. Si la dejas vacía, se mostrará “Sin observaciones”.
            </p>

            <div class="mt-5 rounded-xl bg-slate-50 p-4 dark:bg-slate-800">
                <h2 class="text-sm font-bold text-slate-700 dark:text-slate-200">
                    {{ $multiple ? 'Activos seleccionados' : 'Activo seleccionado' }} ({{ $activos->count() }})
                </h2>
                <ul class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-300">
                    @foreach ($activos as $activo)
                        <li><span class="font-semibold">{{ $activo->codigo_activo }}</span> — {{ $activo->nombre }}</li>
                    @endforeach
                </ul>
            </div>

            @if ($errors->any())
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ $action }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="observaciones" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                        Observaciones (opcional)
                    </label>
                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="5"
                        maxlength="5000"
                        placeholder="Escribe aquí la observación que quieres incluir en el acta..."
                        class="w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500"
                    >{{ old('observaciones') }}</textarea>
                    @error('observaciones')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
                        Generar PDF
                    </button>
                    <a href="{{ url()->previous() }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                        Cancelar
                    </a>
                </div>
            </form>
        </section>
    </div>
@endsection
