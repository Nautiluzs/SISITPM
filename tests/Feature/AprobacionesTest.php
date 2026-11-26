<?php

namespace Tests\Feature;

use App\Models\ActaBaja;
use App\Models\ActaTransferencia;
use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\User;
use App\Models\VerificacionFisica;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * El inventario es de la administradora: nada se aplica sin su aprobación y
 * cada rol solo ve lo que le corresponde.
 */
class AprobacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function activo(array $atributos = []): ActivoFijo
    {
        $categoria = Categoria::firstOrCreate(
            ['nombre' => 'Equipos'],
            ['id_ambiente' => $this->ambiente->id_ambiente]
        );

        $fuente = FuenteFinanciamiento::firstOrCreate(
            ['nombre' => 'Tesorería']
        );

        return ActivoFijo::create(array_merge([
            'codigo_activo' => 'TPM-'.strtoupper(Str::random(8)),
            'nombre' => 'Laptop',
            'id_categoria' => $categoria->id_categoria,
            'id_ambiente' => $this->ambiente->id_ambiente,
            'id_fuente' => $fuente->id_fuente,
            'id_custodio' => $this->custodio->id_usuario,
            'tipo_bien' => 'ACTIVO_FIJO',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => (string) fake()->unique()->numerify('########'),
            'estado_registro' => 'ASIGNADO',
        ], $atributos));
    }

    protected $carrera;

    protected $ambiente;

    protected $otroAmbiente;

    protected $admin;

    protected $inventariador;

    protected $custodio;

    protected $jefe;

    protected $rector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->carrera = Carrera::create(['nombre' => 'Sistemas Informáticos', 'codigo' => 'SIF']);
        $otra = Carrera::create(['nombre' => 'Contabilidad', 'codigo' => 'CON']);

        $this->ambiente = Ambiente::create([
            'nombre' => 'Laboratorio 3', 'codigo' => 'LAB-3', 'id_carrera' => $this->carrera->id_carrera,
        ]);

        $this->otroAmbiente = Ambiente::create([
            'nombre' => 'Laboratorio 2', 'codigo' => 'LAB-2', 'id_carrera' => $otra->id_carrera,
        ]);

        $this->admin = User::factory()->admin()->create();
        $this->inventariador = User::factory()->inventariador()->create();
        $this->custodio = User::factory()->custodio()->create(['id_carrera' => $this->carrera->id_carrera]);
        $this->jefe = User::factory()->jefeCarrera()->create(['id_carrera' => $this->carrera->id_carrera]);
        $this->rector = User::factory()->rector()->create();
    }

    public function test_los_paneles_de_actas_se_abren_sin_errores(): void
    {
        $this->activo();

        foreach (['admin', 'inventariador', 'custodio'] as $rol) {
            $this->actingAs($this->{$rol})
                ->get(route('actas.transferencia.index'))
                ->assertOk();

            $this->actingAs($this->{$rol})
                ->get(route('actas.transferencia.create'))
                ->assertOk();
        }

        $this->actingAs($this->admin)
            ->get(route('actas.baja.index'))
            ->assertOk();
    }

    public function test_custodios_por_ambiente_muestra_usuarios_activos_si_no_hay_asignados(): void
    {
        $response = $this->actingAs($this->inventariador)
            ->getJson(route('actas.transferencia.custodios', $this->otroAmbiente->id_ambiente));

        $response->assertOk()
            ->assertJsonPath('fallback', true)
            ->assertJsonFragment(['id' => $this->rector->id_usuario]);
    }

    public function test_formulario_muestra_usuarios_activos_al_preseleccionar_ambiente_sin_custodios(): void
    {
        $response = $this->actingAs($this->inventariador)
            ->get(route('actas.transferencia.create', ['id_ambiente_destino' => $this->otroAmbiente->id_ambiente]));

        $response->assertOk()
            ->assertSee($this->rector->nombre_completo, false);
    }

    public function test_no_registra_transferencia_con_fecha_futura(): void
    {
        $activo = $this->activo();

        $response = $this->actingAs($this->inventariador)
            ->from(route('actas.transferencia.create'))
            ->post(route('actas.transferencia.store'), [
                'id_activos' => [$activo->id_activo],
                'id_ambiente_destino' => $this->otroAmbiente->id_ambiente,
                'id_custodio' => $this->rector->id_usuario,
                'fecha_transferencia' => now()->addDay()->toDateString(),
            ]);

        $response->assertSessionHasErrors(['fecha_transferencia']);
        $this->assertDatabaseCount('actas_transferencia', 0);
    }

    public function test_una_transferencia_queda_pendiente_y_el_bien_no_se_mueve_hasta_la_aprobacion(): void
    {
        $activo = $this->activo();

        $response = $this->actingAs($this->inventariador)->post(route('actas.transferencia.store'), [
            'id_activos' => [$activo->id_activo],
            'id_ambiente_destino' => $this->otroAmbiente->id_ambiente,
            'id_custodio' => $this->rector->id_usuario,
            'fecha_transferencia' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        $acta = ActaTransferencia::firstOrFail();

        $response->assertRedirect(route('actas.transferencia.show', $acta->id_acta_transferencia));
        $this->assertSame(ActaTransferencia::ESTADO_PENDIENTE, $acta->estado);
        $this->assertDatabaseHas('acta_transferencia_activo', [
            'id_acta_transferencia' => $acta->id_acta_transferencia,
            'id_activo' => $activo->id_activo,
        ]);

        // El bien sigue en su ambiente y con su custodio: solo espera.
        $activo->refresh();
        $this->assertSame('EN_TRANSFERENCIA', $activo->estado_registro);
        $this->assertSame($this->ambiente->id_ambiente, $activo->id_ambiente);
        $this->assertSame($this->custodio->id_usuario, $activo->id_custodio);

        // La administración aprueba: recién ahí se mueve.
        $this->actingAs($this->admin)->post(route('actas.transferencia.aprobar', $acta->id_acta_transferencia))
            ->assertSessionHas('status');

        $activo->refresh();
        $this->assertSame($this->otroAmbiente->id_ambiente, $activo->id_ambiente);
        $this->assertSame('ASIGNADO', $activo->estado_registro);
    }

    public function test_rechazar_una_transferencia_deja_el_bien_como_estaba(): void
    {
        $activo = $this->activo(['estado_registro' => 'ASIGNADO']);

        $this->actingAs($this->inventariador)->post(route('actas.transferencia.store'), [
            'id_activos' => [$activo->id_activo],
            'id_ambiente_destino' => $this->otroAmbiente->id_ambiente,
            'id_custodio' => $this->rector->id_usuario,
            'fecha_transferencia' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $acta = ActaTransferencia::firstOrFail();

        $this->actingAs($this->admin)->post(route('actas.transferencia.rechazar', $acta->id_acta_transferencia));

        $activo->refresh();
        $this->assertSame('ASIGNADO', $activo->estado_registro);
        $this->assertSame($this->ambiente->id_ambiente, $activo->id_ambiente);
    }

    public function test_solo_la_administracion_aprueba_transferencias(): void
    {
        $activo = $this->activo();

        $this->actingAs($this->inventariador)->post(route('actas.transferencia.store'), [
            'id_activos' => [$activo->id_activo],
            'id_ambiente_destino' => $this->otroAmbiente->id_ambiente,
            'id_custodio' => $this->rector->id_usuario,
            'fecha_transferencia' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $acta = ActaTransferencia::firstOrFail();

        $this->actingAs($this->inventariador)
            ->post(route('actas.transferencia.aprobar', $acta->id_acta_transferencia))
            ->assertForbidden();

        $this->actingAs($this->custodio)
            ->post(route('actas.transferencia.aprobar', $acta->id_acta_transferencia))
            ->assertForbidden();

        $this->assertSame(ActaTransferencia::ESTADO_PENDIENTE, $acta->fresh()->estado);
    }

    public function test_una_baja_no_se_aplica_hasta_que_la_administracion_la_aprueba(): void
    {
        $activo = $this->activo();

        $this->actingAs($this->inventariador)->post(route('actas.baja.store'), [
            'activo_ids' => [$activo->id_activo],
            'motivo_baja' => 'Equipo averiado.',
        ])->assertSessionHasNoErrors();

        $acta = ActaBaja::firstOrFail();

        $this->assertSame(ActaBaja::ESTADO_PENDIENTE, $acta->estado);
        $this->assertNotSame('BAJA', $activo->fresh()->estado_registro);

        $this->actingAs($this->admin)->post(route('actas.baja.aprobar', $acta->id_acta_baja))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $this->assertSame('BAJA', $activo->fresh()->estado_registro);
        $this->assertSame(ActaBaja::ESTADO_APROBADA, $acta->fresh()->estado);
    }

    public function test_una_sustitucion_da_de_alta_el_equipo_nuevo_y_guarda_el_viejo_en_el_almacen(): void
    {
        $almacen = Ambiente::create([
            'nombre' => 'Almacén', 'codigo' => 'ALM-1', 'id_carrera' => $this->carrera->id_carrera,
        ]);

        $viejo = $this->activo(['codigo_activo' => 'TPM-VIEJO-1']);

        $this->actingAs($this->inventariador)->post(route('actas.baja.store'), [
            'activo_ids' => [$viejo->id_activo],
            'motivo_baja' => 'Se cambia por el repuesto del almacén.',
            'es_sustitucion' => 1,
            'sustituto_de' => $viejo->id_activo,
            'sustituto_codigo' => 'TPM-NUEVO-1',
            'sustituto_nombre' => 'Laptop de repuesto',
            'sustituto_marca' => 'HP',
        ]);

        $acta = ActaBaja::firstOrFail();
        $this->assertTrue($acta->es_sustitucion);

        $this->actingAs($this->admin)->post(route('actas.baja.aprobar', $acta->id_acta_baja));

        // El viejo queda de baja, guardado en el almacén.
        $viejo->refresh();
        $this->assertSame('BAJA', $viejo->estado_registro);
        $this->assertSame($almacen->id_ambiente, $viejo->id_ambiente);

        // El nuevo entra a trabajar en el mismo ambiente y con el mismo custodio.
        $nuevo = ActivoFijo::where('codigo_activo', 'TPM-NUEVO-1')->first();

        $this->assertNotNull($nuevo);
        $this->assertSame($this->ambiente->id_ambiente, $nuevo->id_ambiente);
        $this->assertSame($this->custodio->id_usuario, $nuevo->id_custodio);
    }

    public function test_un_custodio_solo_ve_los_activos_de_su_ambiente(): void
    {
        $suyo = $this->activo(['codigo_activo' => 'TPM-SUYO-1']);
        $ajeno = $this->activo([
            'codigo_activo' => 'TPM-AJENO-1',
            'id_ambiente' => $this->otroAmbiente->id_ambiente,
            'id_custodio' => $this->rector->id_usuario,
        ]);

        $vistos = ActivoFijo::paraUsuario($this->custodio)->pluck('codigo_activo');

        $this->assertTrue($vistos->contains('TPM-SUYO-1'));
        $this->assertFalse($vistos->contains('TPM-AJENO-1'));

        $this->actingAs($this->custodio)
            ->get(route('activos.index'))
            ->assertOk()
            ->assertSee('TPM-SUYO-1')
            ->assertDontSee('TPM-AJENO-1');

        $this->actingAs($this->custodio)
            ->get(route('activos.tarjeta', $ajeno->id_activo))
            ->assertNotFound();
    }

    public function test_el_jefe_de_carrera_ve_todos_los_activos_de_su_carrera_pero_no_de_otras(): void
    {
        $deSuCarrera = $this->activo(['codigo_activo' => 'TPM-SIF-1']);
        $deOtra = $this->activo([
            'codigo_activo' => 'TPM-CON-1',
            'id_ambiente' => $this->otroAmbiente->id_ambiente,
            'id_custodio' => $this->rector->id_usuario,
        ]);

        $vistos = ActivoFijo::paraUsuario($this->jefe)->pluck('codigo_activo');

        $this->assertTrue($vistos->contains('TPM-SIF-1'));
        $this->assertFalse($vistos->contains('TPM-CON-1'));

        $this->actingAs($this->jefe)
            ->get(route('activos.index'))
            ->assertOk()
            ->assertSee('TPM-SIF-1')
            ->assertDontSee('TPM-CON-1');

        $this->actingAs($this->jefe)->get(route('ambientes.show', [
            'id_carrera' => $this->otroAmbiente->id_carrera,
            'id' => $this->otroAmbiente->id_ambiente,
        ]))
            ->assertNotFound();
    }

    public function test_el_rector_ve_todo_pero_solo_verifica_con_permiso_de_la_administradora(): void
    {
        $deSuCarrera = $this->activo(['codigo_activo' => 'TPM-RECTOR-SIF']);
        $deOtraCarrera = $this->activo([
            'codigo_activo' => 'TPM-RECTOR-CON',
            'id_ambiente' => $this->otroAmbiente->id_ambiente,
            'id_custodio' => $this->rector->id_usuario,
        ]);

        $this->actingAs($this->rector)
            ->get(route('activos.index'))
            ->assertOk()
            ->assertSee($deSuCarrera->codigo_activo)
            ->assertSee($deOtraCarrera->codigo_activo);

        $this->get(route('verificaciones.index'))->assertForbidden();

        $this->actingAs($this->admin)
            ->patch(route('usuarios.toggle_verificar', $this->rector->id_usuario))
            ->assertRedirect();

        $this->rector->refresh();
        $this->assertTrue($this->rector->puede_verificar);

        $this->actingAs($this->rector)
            ->get(route('verificaciones.index'))
            ->assertOk();
    }

    public function test_un_rol_de_solo_lectura_no_registra_verificaciones_ni_solicita_bajas(): void
    {
        $activo = $this->activo();

        $this->actingAs($this->custodio)
            ->get(route('verificaciones.index'))
            ->assertForbidden();

        $this->actingAs($this->custodio)
            ->post(route('verificaciones.guardarItem'), [
                'activo_id' => $activo->id_activo,
                'ambiente_escaneo_id' => $this->ambiente->id_ambiente,
                'es_correspondencia' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($this->custodio)
            ->post(route('actas.baja.store'), [
                'activo_ids' => [$activo->id_activo],
                'motivo_baja' => 'No me sirve.',
            ])
            ->assertForbidden();
    }

    public function test_la_administradora_desactiva_al_ayudante_y_este_no_puede_entrar(): void
    {
        $ayudante = User::factory()->ayudante()->create();

        $this->actingAs($ayudante)->get(route('dashboard'))->assertOk();

        $this->actingAs($this->admin)->put(route('usuarios.update', $ayudante->id_usuario), [
            'nombre_completo' => $ayudante->nombre_completo,
            'ci' => $ayudante->ci,
            'email' => $ayudante->email,
            'cargo' => $ayudante->cargo,
            'unidad' => $ayudante->unidad,
            'rol' => User::ROL_AYUDANTE,
            'estado' => 'INACTIVO',
        ])->assertSessionHasNoErrors();

        $this->assertSame('INACTIVO', $ayudante->fresh()->estado);

        // La sesión viva se corta: vuelve al login.
        // Se relee el usuario de la base, como lo haría una petición real.
        $this->actingAs(User::findOrFail($ayudante->id_usuario))
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_la_administradora_no_puede_quedarse_sin_una_gestion_activa(): void
    {
        $this->actingAs($this->admin)->put(route('usuarios.update', $this->admin->id_usuario), [
            'nombre_completo' => $this->admin->nombre_completo,
            'ci' => $this->admin->ci,
            'email' => $this->admin->email,
            'cargo' => $this->admin->cargo,
            'unidad' => $this->admin->unidad,
            'rol' => User::ROL_ADMINISTRADOR,
            'estado' => 'INACTIVO',
        ])->assertSessionHas('error');

        $this->assertSame('ACTIVO', $this->admin->fresh()->estado);
    }

    public function test_reescanear_un_activo_no_pisa_la_verificacion_ya_aprobada(): void
    {
        $activo = $this->activo();

        $this->actingAs($this->admin)
            ->patch(route('usuarios.toggle_verificar', $this->inventariador->id_usuario))
            ->assertRedirect();
        $this->inventariador->refresh();

        $this->actingAs($this->inventariador)->post(route('verificaciones.guardarItem'), [
            'activo_id' => $activo->id_activo,
            'ambiente_escaneo_id' => $this->ambiente->id_ambiente,
            'es_correspondencia' => 1,
            'estado_fisico' => 'B',
        ])->assertOk();

        $verificacion = VerificacionFisica::firstOrFail();

        $this->actingAs($this->admin)->post(route('verificaciones-aprobacion.aprobar', $verificacion->id_verificacion));

        $this->assertSame(VerificacionFisica::ESTADO_APROBADA, $verificacion->fresh()->estado_aprobacion);

        // Segundo escaneo: la aprobación anterior se conserva.
        $this->actingAs($this->inventariador)->post(route('verificaciones.guardarItem'), [
            'activo_id' => $activo->id_activo,
            'ambiente_escaneo_id' => $this->ambiente->id_ambiente,
            'es_correspondencia' => 1,
            'estado_fisico' => 'R',
        ])->assertOk();

        $this->assertSame(2, VerificacionFisica::count());
        $this->assertSame(VerificacionFisica::ESTADO_APROBADA, $verificacion->fresh()->estado_aprobacion);
        $this->assertSame(VerificacionFisica::ESTADO_PENDIENTE, VerificacionFisica::latest('id_verificacion')->first()->estado_aprobacion);
    }

    public function test_el_qr_devuelve_custodio_ambiente_y_si_es_mio(): void
    {
        $activo = $this->activo(['codigo_activo' => 'TPM-QR-1']);

        $this->actingAs($this->custodio)
            ->getJson(route('activos.buscarPorCodigo', ['codigo' => 'TPM-QR-1']))
            ->assertOk()
            ->assertJsonPath('activo.custodio', $this->custodio->nombre_completo)
            ->assertJsonPath('activo.ambiente', 'Laboratorio 3')
            ->assertJsonPath('es_mi_activo', true);
    }

    public function test_la_sincronizacion_offline_registra_y_respeta_el_ambito(): void
    {
        // Un docente al que la administradora habilitó la verificación:
        // su alcance son los activos de su ambiente, no todo el inventario.
        $verificador = User::findOrFail($this->custodio->id_usuario);
        $verificador->update(['puede_verificar' => true]);

        $suyo = $this->activo(['codigo_activo' => 'TPM-OFF-1']);
        $ajeno = $this->activo([
            'codigo_activo' => 'TPM-OFF-2',
            'id_ambiente' => $this->otroAmbiente->id_ambiente,
            'id_custodio' => $this->rector->id_usuario,
        ]);

        $response = $this->actingAs($verificador)
            ->postJson(route('sincronizacion.verificaciones'), [
                'verificaciones' => [
                    [
                        'id_local' => 'v-1',
                        'codigo_activo' => $suyo->codigo_activo,
                        'ambiente_escaneo_id' => $this->ambiente->id_ambiente,
                        'fecha_verificacion' => now()->toIso8601String(),
                        'estado_fisico' => 'B',
                    ],
                    [
                        'id_local' => 'v-2',
                        'codigo_activo' => $ajeno->codigo_activo,
                        'ambiente_escaneo_id' => $this->otroAmbiente->id_ambiente,
                        'fecha_verificacion' => now()->toIso8601String(),
                    ],
                    [
                        'id_local' => 'v-3',
                        'codigo_activo' => $suyo->codigo_activo,
                        'ambiente_escaneo_id' => $this->otroAmbiente->id_ambiente,
                        'fecha_verificacion' => now()->toIso8601String(),
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        // El ajeno no entra: el dispositivo solo debe borrar lo ya guardado.
        $this->assertSame(['v-1'], array_column($response->json('registradas'), 'id_local'));
        $this->assertSame(['v-2', 'v-3'], array_column($response->json('rechazadas'), 'id_local'));

        $this->assertSame(1, VerificacionFisica::count());

        $creada = VerificacionFisica::first();
        $this->assertTrue($creada->sincronizado_offline);
        $this->assertSame($verificador->id_usuario, $creada->id_verificador);
        $this->assertSame(VerificacionFisica::ESTADO_PENDIENTE, $creada->estado_aprobacion);
    }

    public function test_verificaciones_muestran_los_30_activos_del_ambiente_y_aislan_otro_ambiente(): void
    {
        $activos = collect();
        for ($numero = 1; $numero <= 30; $numero++) {
            $activos->push($this->activo([
                'codigo_activo' => sprintf('TPM-LAB3-%02d', $numero),
            ]));
        }

        VerificacionFisica::create([
            'id_activo' => $activos->first()->id_activo,
            'id_ambiente_escaneo' => $this->otroAmbiente->id_ambiente,
            'id_verificador' => $this->admin->id_usuario,
            'fecha_verificacion' => now(),
            'es_correspondencia_correcta' => false,
            'observaciones' => 'Historial de otro ambiente.',
            'estado_aprobacion' => VerificacionFisica::ESTADO_PENDIENTE,
        ]);

        $activoDeOtroAmbiente = $this->activo([
            'codigo_activo' => 'TPM-LAB2-01',
            'id_ambiente' => $this->otroAmbiente->id_ambiente,
            'id_custodio' => $this->rector->id_usuario,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('verificaciones.guardarItem'), [
                'activo_id' => $activoDeOtroAmbiente->id_activo,
                'ambiente_escaneo_id' => $this->ambiente->id_ambiente,
                'es_correspondencia' => false,
            ])
            ->assertOk()
            ->assertJsonPath('verificacion.observaciones', 'Activo registrado en Laboratorio 2 y encontrado en Laboratorio 3.');

        $response = $this->actingAs($this->admin)->get(route('verificaciones.index', [
            'carrera_id' => $this->carrera->id_carrera,
            'ambiente_id' => $this->ambiente->id_ambiente,
        ]));

        $response->assertOk()
            ->assertSee('30 activo(s) en este ambiente')
            ->assertSee('TPM-LAB3-01')
            ->assertSee('TPM-LAB3-30')
            ->assertDontSee('Historial de otro ambiente.')
            ->assertDontSee('TPM-LAB2-01');
    }

    public function test_finalizar_exige_ubicacion_de_activos_faltantes_y_no_envia_correo(): void
    {
        $verificado = $this->activo(['codigo_activo' => 'TPM-LAB3-VERIFICADO']);
        $faltante = $this->activo(['codigo_activo' => 'TPM-LAB3-FALTANTE']);
        Mail::fake();

        $this->actingAs($this->admin)
            ->postJson(route('verificaciones.guardarItem'), [
                'activo_id' => $verificado->id_activo,
                'ambiente_escaneo_id' => $this->ambiente->id_ambiente,
                'es_correspondencia' => true,
            ])
            ->assertOk();

        $this->postJson(route('verificaciones.finalizar'), [
            'ambiente_id' => $this->ambiente->id_ambiente,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('faltantes.0.id_activo', $faltante->id_activo)
            ->assertJsonPath('faltantes.0.ambiente_esperado', 'Laboratorio 3');

        $this->assertDatabaseMissing('verificaciones_fisicas', [
            'id_activo' => $faltante->id_activo,
            'id_ambiente_escaneo' => $this->ambiente->id_ambiente,
        ]);

        $this->postJson(route('verificaciones.finalizar'), [
            'ambiente_id' => $this->ambiente->id_ambiente,
            'faltantes' => [[
                'id_activo' => $faltante->id_activo,
                'ubicacion' => 'Almacén central',
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_verificados', 1)
            ->assertJsonPath('total_observados', 1);

        $this->assertDatabaseHas('verificaciones_fisicas', [
            'id_activo' => $faltante->id_activo,
            'id_ambiente_escaneo' => $this->ambiente->id_ambiente,
            'observaciones' => 'No localizado en el ambiente esperado (Laboratorio 3). Ubicación reportada: Almacén central',
            'verificacion_finalizada' => true,
        ]);
        Mail::assertNothingOutgoing();
    }

    public function test_dashboard_muestra_datos_mensuales_reales_en_grafica_de_barras(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 12:00:00'));

        $activoEneroUno = $this->activo(['codigo_activo' => 'TPM-ENE-01']);
        $activoEneroDos = $this->activo(['codigo_activo' => 'TPM-ENE-02']);
        $this->activo(['codigo_activo' => 'TPM-FEB-01']);

        DB::table('activos_fijos')->where('id_activo', $activoEneroUno->id_activo)
            ->update(['created_at' => '2026-01-05 10:00:00']);
        DB::table('activos_fijos')->where('id_activo', $activoEneroDos->id_activo)
            ->update(['created_at' => '2026-01-20 10:00:00']);

        $mesConMasRegistros = Carbon::parse('2026-01-01')->translatedFormat('M Y');

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee("Más registros: {$mesConMasRegistros} (2)")
            ->assertSee("type: 'bar'", false)
            ->assertViewHas('maxRegistrosMensual', 2)
            ->assertViewHas('mesesConMasRegistros', [$mesConMasRegistros]);
    }
}
