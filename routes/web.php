<?php

use App\Http\Controllers\AprobacionVerificacionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\ActaBajaController;
use App\Http\Controllers\Web\ActaRecepcionController;
use App\Http\Controllers\Web\ActaTransferenciaController;
use App\Http\Controllers\Web\ActivoController;
use App\Http\Controllers\Web\AmbienteController;
use App\Http\Controllers\Web\AprobacionController;
use App\Http\Controllers\Web\BackupController;
use App\Http\Controllers\Web\CarreraController;
use App\Http\Controllers\Web\CategoriaController;
use App\Http\Controllers\Web\ConfiguracionController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ImportarActivosController;
use App\Http\Controllers\Web\ItemController;
use App\Http\Controllers\Web\ReporteController;
use App\Http\Controllers\Web\SincronizacionController;
use App\Http\Controllers\Web\UsuarioController;
use App\Http\Controllers\Web\VerificacionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// ============================================================
// RUTA RAÍZ
// ============================================================
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// ============================================================
// SIN CONEXIÓN
// ============================================================
Route::get('/offline', function () {
    return Auth::check()
        ? view('offline')
        : redirect()->route('login');
})->name('offline');

// ============================================================
// RUTAS DE AUTENTICACIÓN (públicas)
// ============================================================
Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login')
    ->middleware('guest');

Route::post('/login', [LoginController::class, 'login'])
    ->middleware('guest');

Route::get('/login/qr', [LoginController::class, 'showQrLogin'])
    ->name('login.qr')
    ->middleware('guest');

Route::post('/login/qr', [LoginController::class, 'loginQr'])
    ->name('login.qr.post')
    ->middleware('guest');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

Route::post('/login/clear-welcome', [LoginController::class, 'clearWelcome'])
    ->name('login.clear-welcome')
    ->middleware('auth');

// ============================================================
// RUTAS PROTEGIDAS
// ============================================================
Route::middleware(['auth', 'usuario.activo'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/manual', function () {
        return view('manual.index');
    })->name('manual.index');
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/texto-cumplimiento', [ConfiguracionController::class, 'updateTextoCumplimiento'])->name('configuracion.texto-cumplimiento');
    Route::post('/configuracion/texto-legal', [ConfiguracionController::class, 'updateTextoLegal'])->name('configuracion.texto-legal');

    // Perfil
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // ==========================================================
    // USUARIOS — Solo ADMIN
    // ==========================================================
    Route::prefix('usuarios')->name('usuarios.')->middleware('rol:ADMINISTRADOR')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::get('/buscar', [UsuarioController::class, 'buscar'])->name('buscar');
        Route::post('/', [UsuarioController::class, 'store'])->name('store');
        Route::post('/reenviar-credenciales-seleccionados', [UsuarioController::class, 'reenviarCredencialesSeleccionados'])->name('reenviar_credenciales_seleccionados');
        Route::post('/reenviar-credenciales-todos', [UsuarioController::class, 'reenviarCredencialesTodos'])->name('reenviar_credenciales_todos');
        Route::post('/eliminar-seleccionados', [UsuarioController::class, 'destroySeleccionados'])->name('destroy_seleccionados');
        Route::put('/{id}', [UsuarioController::class, 'update'])->name('update');
        Route::patch('/{id}/toggle-verificar', [UsuarioController::class, 'toggleVerificar'])->name('toggle_verificar');
        Route::get('/create', [UsuarioController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [UsuarioController::class, 'edit'])->name('edit');
        Route::delete('/{id}', [UsuarioController::class, 'destroy'])->name('destroy');
        Route::get('/{id}/qr', [UsuarioController::class, 'generarQr'])->name('qr');
        Route::post('/{id}/enviar-credenciales', [UsuarioController::class, 'enviarCredenciales'])->name('enviar_credenciales');
    });

    // ==========================================================
    // ITEMS
    // ==========================================================
    Route::prefix('items')->name('items.')->middleware('rol:ADMINISTRADOR')->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('index');
        Route::get('/buscar', [ItemController::class, 'buscar'])->name('buscar');
        Route::get('/crear', [ItemController::class, 'create'])->name('create');
        Route::get('/{numeroItem}', [ItemController::class, 'show'])->name('show');

        Route::post('/', [ItemController::class, 'store'])->name('store');
        Route::get('/{id}/editar', [ItemController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ItemController::class, 'update'])->name('update');
        Route::delete('/{id}', [ItemController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/custodios', [ItemController::class, 'asignarCustodio'])->name('custodios.store');
        Route::delete('/{id}/custodios/{idUsuario}', [ItemController::class, 'desvincularCustodio'])->name('custodios.destroy');
    });

    // ==========================================================
    // CARRERAS Y AMBIENTES ANIDADOS
    // ==========================================================
    Route::prefix('carreras')->group(function () {
        Route::get('/', [CarreraController::class, 'index'])->name('carreras.index');

        Route::middleware('rol:ADMINISTRADOR')->group(function () {
            Route::get('/crear', [CarreraController::class, 'create'])->name('carreras.create');
        });

        Route::get('/{id_carrera}', [CarreraController::class, 'show'])->name('carreras.show');

        Route::middleware('rol:ADMINISTRADOR')->group(function () {
            Route::get('/{id_carrera}/editar', [CarreraController::class, 'edit'])->name('carreras.edit');
            Route::post('/', [CarreraController::class, 'store'])->name('carreras.store');
            Route::put('/{id_carrera}', [CarreraController::class, 'update'])->name('carreras.update');
            Route::delete('/{id_carrera}', [CarreraController::class, 'destroy'])->name('carreras.destroy');
        });

        Route::prefix('{id_carrera}/ambientes')->name('ambientes.')->group(function () {
            Route::get('/', [AmbienteController::class, 'index'])->name('index');
            Route::get('/crear', [AmbienteController::class, 'create'])->name('create');

            Route::middleware('rol:ADMINISTRADOR')->group(function () {
                Route::post('/', [AmbienteController::class, 'store'])->name('store');
                Route::put('/{id_ambiente}', [AmbienteController::class, 'update'])->name('update');
                Route::delete('/{id_ambiente}', [AmbienteController::class, 'destroy'])->name('destroy');
            });
        });
    });

    Route::prefix('ambientes')->name('ambientes.')->group(function () {
        Route::get('/{id}', [AmbienteController::class, 'show'])->name('show');
    });

    // ==========================================================
    // CATEGORÍAS
    // ==========================================================
    Route::prefix('categorias')->name('categorias.')->group(function () {
        Route::get('/', [CategoriaController::class, 'index'])->name('index');

        Route::middleware('rol:ADMINISTRADOR')->group(function () {
            Route::post('/', [CategoriaController::class, 'store'])->name('store');
            Route::put('/{id}', [CategoriaController::class, 'update'])->name('update');
            Route::delete('/{id}', [CategoriaController::class, 'destroy'])->name('destroy');
        });

        Route::get('/{id}', [ActivoController::class, 'show'])->name('show');
    });

    // ==========================================================
    // ACTIVOS
    // ==========================================================
    Route::prefix('activos')->name('activos.')->group(function () {
        Route::get('/create', [ActivoController::class, 'create'])->name('create')->middleware('rol:ADMINISTRADOR');
        Route::get('/exportar/pdf', [ActivoController::class, 'pdf'])->name('pdf');
        Route::get('/exportar/excel', [ActivoController::class, 'excel'])->name('excel');
        Route::get('/codigo/{codigo?}', [ActivoController::class, 'buscarPorCodigo'])->name('buscarPorCodigo');
        Route::get('/no-activos', [ActivoController::class, 'noActivos'])->name('noActivos');
        Route::get('/grupo/{id_categoria}/{nombre}', [ActivoController::class, 'verGrupo'])->name('grupo');

        Route::get('/', [ActivoController::class, 'index'])->name('index');
        Route::post('/', [ActivoController::class, 'store'])->name('store')->middleware('rol:ADMINISTRADOR');
        Route::get('/{id}/edit', [ActivoController::class, 'edit'])->name('edit')->middleware('rol:ADMINISTRADOR');
        Route::put('/{id}', [ActivoController::class, 'update'])->name('update')->middleware('rol:ADMINISTRADOR');
        Route::delete('/eliminar-seleccionados', [ActivoController::class, 'destroyMany'])->name('destroyMany')->middleware('rol:ADMINISTRADOR');
        Route::delete('/{id}', [ActivoController::class, 'destroy'])->name('destroy')->middleware('rol:ADMINISTRADOR');

        Route::get('/{id}/tarjeta', [ActivoController::class, 'tarjeta'])->name('tarjeta');
        Route::get('/{id}/qr', [ActivoController::class, 'descargarQr'])->name('descargar_qr');
        Route::get('/{id}/etiqueta', [ActivoController::class, 'etiqueta'])->name('etiqueta');
        Route::get('/etiquetas', [ActivoController::class, 'etiquetas'])->name('etiquetas');
        Route::get('/custodios/{idAmbiente}', [ActivoController::class, 'custodiosPorAmbiente'])->name('custodios');

        // Importación masiva desde Excel o CSV
        Route::get('/importar/form', [ImportarActivosController::class, 'form'])->name('importar.form');
        Route::post('/importar', [ImportarActivosController::class, 'store'])->name('importar.store');
        Route::get('/importar/plantilla', [ImportarActivosController::class, 'template'])->name('importar.template');
    });

    // ==========================================================
    // REPORTES
    // ==========================================================
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');

        Route::get('/inventario-general', [ReporteController::class, 'inventarioGeneral'])->name('inventario.general');
        Route::get('/inventario-general/pdf', [ReporteController::class, 'inventarioGeneralPdf'])->name('inventario.general.pdf');
        Route::get('/inventario-general/excel', [ReporteController::class, 'inventarioGeneralExcel'])->name('inventario.general.excel');

        Route::get('/por-ambiente/{id_ambiente}', [ReporteController::class, 'porAmbiente'])->name('por.ambiente');
        Route::get('/por-ambiente/{id_ambiente}/pdf', [ReporteController::class, 'porAmbientePdf'])->name('por.ambiente.pdf');
        Route::get('/por-ambiente/{id_ambiente}/excel', [ReporteController::class, 'porAmbienteExcel'])->name('por.ambiente.excel');

        Route::get('/aula/{id_ambiente}', [ReporteController::class, 'porAula'])->name('aula');
        Route::get('/aula/{id_ambiente}/pdf', [ReporteController::class, 'porAulaPdf'])->name('aula.pdf');
        Route::get('/aula/{id_ambiente}/excel', [ReporteController::class, 'porAulaExcel'])->name('aula.excel');

        Route::get('/carrera/{id_carrera}', [ReporteController::class, 'porCarrera'])->name('por.carrera');
        Route::get('/carrera/{id_carrera}/pdf', [ReporteController::class, 'porCarreraPdf'])->name('por.carrera.pdf');

        Route::get('/categoria/{idCategoria}', [ReporteController::class, 'porCategoria'])->name('categoria');
        Route::get('/categoria/{idCategoria}/pdf', [ReporteController::class, 'porCategoriaPdf'])->name('categoria.pdf');

        Route::get('/pdf', [ReporteController::class, 'pdf'])->name('pdf');
        Route::get('/excel', [ReporteController::class, 'excel'])->name('excel');
    });

    // ==========================================================
    // VERIFICACIONES
    // ==========================================================
    Route::prefix('verificaciones')->name('verificaciones.')
        ->middleware('puede.verificar')
        ->group(function () {
            Route::get('/', [VerificacionController::class, 'index'])->name('index');
            Route::post('/guardar-item', [VerificacionController::class, 'guardarItem'])->name('guardarItem');
            Route::post('/finalizar', [VerificacionController::class, 'finalizar'])->name('finalizar');
            Route::match(['get', 'post'], '/exportar/pdf', [VerificacionController::class, 'exportarPdf'])->name('pdf');
            Route::match(['get', 'post'], '/exportar/excel', [VerificacionController::class, 'exportarExcel'])->name('excel');
            Route::get('/categorias-por-ambiente', [VerificacionController::class, 'categoriasPorAmbiente'])->name('categoriasPorAmbiente');
        });

    // ==========================================================
    // SINCRONIZACIÓN OFFLINE
    // ==========================================================
    Route::prefix('sincronizacion')->name('sincronizacion.')->group(function () {
        Route::get('/catalogo', [SincronizacionController::class, 'catalogo'])->name('catalogo');
        Route::post('/verificaciones', [SincronizacionController::class, 'verificaciones'])->name('verificaciones');
    });

    // ==========================================================
    // APROBACIONES Y VERIFICACIONES (ADMINISTRADOR)
    // ==========================================================
    Route::get('/aprobaciones', [AprobacionController::class, 'index'])
        ->name('aprobaciones.index')
        ->middleware('rol:ADMINISTRADOR');

    Route::prefix('verificaciones-aprobacion')->name('verificaciones-aprobacion.')
        ->middleware('rol:ADMINISTRADOR')
        ->group(function () {
            Route::get('/', [AprobacionVerificacionController::class, 'index'])->name('index');
            Route::post('/{id}/aprobar', [AprobacionVerificacionController::class, 'aprobar'])->name('aprobar');
            Route::post('/{id}/rechazar', [AprobacionVerificacionController::class, 'rechazar'])->name('rechazar');
            Route::post('/{id}/corregir', [AprobacionVerificacionController::class, 'corregir'])->name('corregir');
            Route::post('/ambiente/{ambienteId}/aprobar-correctas', [AprobacionVerificacionController::class, 'aprobarCorrectasDeAmbiente'])->name('aprobar-ambiente');
        });

    Route::post('/notificar-finalizacion', [AprobacionVerificacionController::class, 'notificarFinalizacion'])
        ->name('notificar-finalizacion');

    // ==========================================================
    // ACTAS (BAJA, TRANSFERENCIA, RECEPCIÓN INDIVIDUAL, LIBERACIÓN)
    // ==========================================================
    Route::prefix('actas')->name('actas.')->group(function () {

        // Acta de Baja
        Route::prefix('baja')->name('baja.')->group(function () {
            Route::get('/', [ActaBajaController::class, 'index'])->name('index');
            Route::get('/crear', [ActaBajaController::class, 'create'])->name('create');
            Route::post('/', [ActaBajaController::class, 'store'])->name('store');
            Route::get('/{acta}', [ActaBajaController::class, 'show'])->name('show');
            Route::get('/{acta}/pdf', [ActaBajaController::class, 'pdf'])->name('pdf');
            Route::get('/{acta}/excel', [ActaBajaController::class, 'excel'])->name('excel');

            Route::middleware('rol:ADMINISTRADOR')->group(function () {
                Route::post('/{acta}/aprobar', [ActaBajaController::class, 'aprobar'])->name('aprobar');
                Route::post('/{acta}/rechazar', [ActaBajaController::class, 'rechazar'])->name('rechazar');
            });
        });

        // Acta de Transferencia
        Route::prefix('transferencia')->name('transferencia.')->group(function () {
            Route::get('/', [ActaTransferenciaController::class, 'index'])->name('index');
            Route::get('/crear', [ActaTransferenciaController::class, 'create'])->name('create');
            Route::post('/', [ActaTransferenciaController::class, 'store'])->name('store');
            Route::get('/{acta}', [ActaTransferenciaController::class, 'show'])->name('show');
            Route::get('/{acta}/pdf', [ActaTransferenciaController::class, 'pdf'])->name('pdf');
            Route::get('/{acta}/excel', [ActaTransferenciaController::class, 'excel'])->name('excel');
            Route::get('/custodios/{idAmbiente}', [ActaTransferenciaController::class, 'custodiosPorAmbiente'])->name('custodios');

            Route::middleware('rol:ADMINISTRADOR')->group(function () {
                Route::post('/{acta}/aprobar', [ActaTransferenciaController::class, 'aprobar'])->name('aprobar');
                Route::post('/{acta}/rechazar', [ActaTransferenciaController::class, 'rechazar'])->name('rechazar');
            });
        });

        // Acta de Recepción (General e Individual PDF / Excel)
        Route::prefix('recepcion')->name('recepcion.')->group(function () {
            Route::get('/', [ActaRecepcionController::class, 'index'])->name('index');
            Route::get('/pdf/{id}', [ActaRecepcionController::class, 'pdf'])->name('pdf');
            Route::post('/pdf/{id}', [ActaRecepcionController::class, 'generarPdf'])->name('pdf.generate');
            Route::get('/pdf-multiple', [ActaRecepcionController::class, 'pdfMultiple'])->name('pdf.multiple');
            Route::post('/pdf-multiple', [ActaRecepcionController::class, 'generarPdfMultiple'])->name('pdf.multiple.generate');
            Route::get('/activo/{activo}/pdf', [ActaRecepcionController::class, 'actaRecepcionActivoPdf'])->name('activo.pdf');
            Route::get('/activo/{activo}/excel', [ActaRecepcionController::class, 'actaRecepcionActivoExcel'])->name('activo.excel');
        });

    });

    // ==========================================================
    // CONFIGURACIÓN Y BACKUPS — Solo ADMIN
    // ==========================================================
    Route::middleware('rol:ADMINISTRADOR')->group(function () {

        Route::prefix('backup')->name('backup.')->group(function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::post('/crear', [BackupController::class, 'crear'])->name('crear');
            Route::get('/descargar/{archivo}', [BackupController::class, 'descargar'])->name('descargar')->where('archivo', '.*');
            Route::delete('/eliminar/{archivo}', [BackupController::class, 'eliminar'])->name('eliminar')->where('archivo', '.*');
            Route::post('/importar', [BackupController::class, 'importar'])->name('importar');
        });
    });
    Route::middleware('rol:ADMINISTRADOR')->group(function () {
        // ... tus otras rutas ...
        Route::post('/ambientes/{id}/custodios', [AmbienteController::class, 'asignarCustodios'])->name('ambientes.custodios.store');
    });
    Route::prefix('actas/baja')->name('actas.baja.')->group(function () {
        // ... tus otras rutas existentes ...
        Route::post('/{acta}/dar-de-alta', [ActaBajaController::class, 'darDeAlta'])->name('darDeAlta');
    });
    // Listado de activos dados de baja
    Route::get('/activos/baja', [ActivoController::class, 'activosBaja'])->name('activos.baja');
});
