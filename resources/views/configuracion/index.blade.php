@extends('layouts.app')

@section('title', 'Configuraciones')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-6 text-slate-800 dark:text-slate-100">
    <div class="mb-6 rounded-2xl bg-gradient-to-r from-blue-700 via-indigo-700 to-violet-700 p-7 text-white shadow-xl">
        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-extrabold uppercase tracking-wider">Preferencias</span>
        <h1 class="mt-3 text-3xl font-black">Configuraciones del sistema</h1>
        <p class="mt-2 text-sm text-blue-100">Personaliza la apariencia de SISActivos desde este navegador.</p>
    </div>

    <section class="grid gap-5 md:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-2 text-lg font-black text-slate-900 dark:text-white">Tema de la interfaz</h2>
            <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">Elige la combinación de colores de la interfaz.</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <button type="button" data-theme-option="light" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800 transition hover:border-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    ☀️ Blanco
                </button>
                <button type="button" data-theme-option="dark" class="rounded-xl border border-slate-300 bg-slate-800 px-4 py-3 text-sm font-bold text-white transition hover:border-blue-400">
                    🌙 Oscuro
                </button>
                <button type="button" data-theme-option="blue-red" class="rounded-xl border border-red-300 bg-gradient-to-r from-blue-800 to-red-600 px-4 py-3 text-sm font-bold text-white transition hover:from-blue-900 hover:to-red-700">
                    🔵🔴 Azul y rojo
                </button>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="mb-2 text-lg font-black text-slate-900 dark:text-white">Tamaño de letra</h2>
            <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">Ajusta el tamaño para leer mejor el sistema.</p>
            <div class="grid grid-cols-3 gap-3">
                <button type="button" data-font-option="small" class="rounded-xl border border-slate-300 px-3 py-3 text-sm font-bold text-slate-800 transition hover:border-blue-500 dark:border-slate-600 dark:text-white">A Pequeña</button>
                <button type="button" data-font-option="normal" class="rounded-xl border border-slate-300 px-3 py-3 text-base font-bold text-slate-800 transition hover:border-blue-500 dark:border-slate-600 dark:text-white">A Normal</button>
                <button type="button" data-font-option="large" class="rounded-xl border border-slate-300 px-3 py-3 text-lg font-bold text-slate-800 transition hover:border-blue-500 dark:border-slate-600 dark:text-white">A Grande</button>
            </div>
        </article>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-theme-option]').forEach(function (button) {
            button.addEventListener('click', function () {
            const theme = button.dataset.themeOption;
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.dataset.colorTheme = theme === 'blue-red' ? 'blue-red' : 'default';
            localStorage.setItem('sisactivos-theme', theme === 'dark' ? 'dark' : 'light');
            localStorage.setItem('sisactivos-color-theme', theme === 'blue-red' ? 'blue-red' : 'default');
        });
        });

        document.querySelectorAll('[data-font-option]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.documentElement.dataset.fontSize = button.dataset.fontOption;
                localStorage.setItem('sisactivos-font-size', button.dataset.fontOption);
            });
        });
    });
</script>
@endpush
