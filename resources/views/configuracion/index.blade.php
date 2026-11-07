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

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h2 class="mb-2 text-lg font-black text-slate-900 dark:text-white">Texto de cumplimiento del acta</h2>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Texto que aparece antes de la lista de activos. Si lo dejas vacío, se usa el texto por defecto.</p>
        <form action="{{ route('configuracion.texto-cumplimiento') }}" method="POST" class="space-y-3">
            @csrf
            <textarea name="texto_cumplimiento" rows="4" class="w-full rounded-xl border border-slate-300 bg-slate-50 p-3 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ $textoCumplimiento ?: 'En cumplimiento al D.S. 0181 Normas Básicas del Sistema de Bienes y Servicios en su Art. 146, 147, 157 (Asignación de Activos Fijos Muebles), la sección de Activos Fijos del Instituto Tecnológico "Puerto de Mejillones", procedió a la verificación y asignación de los siguientes activos:' }}</textarea>
            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-bold text-white hover:bg-blue-700">Guardar</button>
        </form>
    </section>

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h2 class="mb-2 text-lg font-black text-slate-900 dark:text-white">Texto legal del acta de recepción</h2>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">Edita el texto que aparece al final del PDF del acta. Si lo dejas vacío, se usa el texto por defecto.</p>
        <form action="{{ route('configuracion.texto-legal') }}" method="POST" class="space-y-3">
            @csrf
            <textarea name="texto_legal" rows="6" class="w-full rounded-xl border border-slate-300 bg-slate-50 p-3 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ $textoLegal ?: 'IMPORTANTE: Como responsable del bien de uso me comprometo a dar cumplimiento al D.S.0181 Art. 116 (RESPONSABILIDAD POR EL MANEJO DE BIENES) Parágrafo III. Todos los servidores
Publicos son responsables por el debido uso, custodia, preservacion y demanda de servicios de mantenimiento de los bienes que les fueran asignados, de acuerdo el régimen de Responsabilidades Por la
funcion Publica, establecida en la Ley 1178 y sus reglamentos.
Art.157(PROHIBICIONES PARA LOS FUNCIONARIOS PÚBLICOS SOBRE EL USO DE ACTIVOS FIJOS MUEBLES)Inc A,b,c,d,e,f,h,g
NOTA: COMO RESPONSABLE DEL USO Y CUSTODIO ME COMPROMETO CUMPLIR EL INCISO c: PRESTAR O TRANSFERIR EL BIEN A OTRO EMPLEADO PUBLICO.' }}</textarea>
            <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-bold text-white hover:bg-blue-700">Guardar</button>
        </form>
        @if(session('success'))
            <p class="mt-2 text-sm text-emerald-600">{{ session('success') }}</p>
        @endif
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
