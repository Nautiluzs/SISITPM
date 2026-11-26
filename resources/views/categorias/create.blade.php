@extends('layouts.app')

@section('title', 'Crear categoría')

@section('content')
<div class="container-fluid px-4 py-4">
    <section style="max-width:680px;margin:0 auto;background:white;border:1px solid #e2e8f0;border-radius:16px;padding:28px;">
        <h1 style="font-size:24px;font-weight:800;color:#0f172a;margin:0 0 8px;">Crear categoría</h1>
        <p style="color:#64748b;font-size:14px;margin:0 0 22px;">Registra una categoría compartida para clasificar activos.</p>

        @if ($errors->any())
            <div style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:14px 18px;border-radius:10px;margin-bottom:18px;">
                <ul style="margin:0;padding-left:20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('categorias.store') }}" style="display:grid;gap:16px;">
            @csrf
            <label style="display:grid;gap:6px;font-size:13px;font-weight:700;color:#334155;">
                Nombre
                <input name="nombre" value="{{ old('nombre') }}" maxlength="100" required style="width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:8px;">
            </label>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <a href="{{ route('categorias.index') }}" style="background:#e2e8f0;color:#334155;padding:10px 15px;border-radius:8px;font-weight:700;text-decoration:none;">Cancelar</a>
                <button type="submit" style="background:#2563eb;color:#fff;border:0;padding:10px 16px;border-radius:8px;font-weight:800;cursor:pointer;">Guardar categoría</button>
            </div>
        </form>
    </section>
</div>
@endsection
