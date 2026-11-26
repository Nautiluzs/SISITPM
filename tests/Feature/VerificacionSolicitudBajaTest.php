<?php

namespace Tests\Feature;

use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\User;
use App\Models\VerificacionFisica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cuando quien verifica pide la baja de un bien, la verificación queda
 * registrada y a la espera de que la administración la resuelva: no se da de
 * baja al instante.
 */
class VerificacionSolicitudBajaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $verificador;

    protected Ambiente $ambiente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->verificador = User::factory()->custodio()->create([
            'puede_verificar' => true,
        ]);

        $carrera = Carrera::create(['codigo' => 'SIF', 'nombre' => 'Sistemas Informáticos']);

        $this->ambiente = Ambiente::create([
            'codigo' => 'LAB-01',
            'nombre' => 'Laboratorio 1',
            'bloque' => 'A',
            'piso' => 1,
            'id_carrera' => $carrera->id_carrera,
        ]);
    }

    protected function activo(): ActivoFijo
    {
        $categoria = Categoria::create([
            'nombre' => 'Equipo de cómputo',
        ]);

        $fuente = FuenteFinanciamiento::create([
            'codigo' => 'ADQ',
            'nombre' => 'Adquisición',
        ]);

        return ActivoFijo::create([
            'codigo_activo' => 'ACT-1001',
            'nombre' => 'Laptop de pruebas',
            'descripcion' => 'Equipo de laboratorio',
            'tipo_bien' => 'ACTIVO_FIJO',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'numero_serie' => 'SN-ACT-1001',
            'estado_fisico' => 'B',
            'estado_registro' => 'ASIGNADO',
            'id_categoria' => $categoria->id_categoria,
            'id_ambiente' => $this->ambiente->id_ambiente,
            'id_fuente' => $fuente->id_fuente,
            'id_custodio' => $this->verificador->id_usuario,
        ]);
    }

    public function test_la_solicitud_de_baja_queda_registrada_y_pendiente_de_aprobacion(): void
    {
        $activo = $this->activo();

        $this->actingAs($this->verificador)
            ->postJson(route('verificaciones.guardarItem'), [
                'activo_id' => $activo->id_activo,
                'ambiente_escaneo_id' => $this->ambiente->id_ambiente,
                'es_correspondencia' => false,
                'observaciones' => 'Pantalla rota y sin cargador',
                'estado_fisico' => 'FF',
                'solicitar_baja' => true,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $verificacion = VerificacionFisica::firstOrFail();

        $this->assertSame($this->verificador->id_usuario, $verificacion->id_verificador);
        $this->assertTrue($verificacion->solicitar_baja);
        // La correspondencia la decide el servidor comparando el ambiente
        // escaneado con el registrado, no el dispositivo.
        $this->assertTrue($verificacion->es_correspondencia_correcta);
        $this->assertSame('FF', $verificacion->estado_fisico_solicitado);
        $this->assertStringContainsString('[Solicitar baja del activo]', $verificacion->observaciones);

        // El bien no se toca: la baja es un acta aparte, y la administración
        // tiene que aprobarla.
        $this->assertSame('ASIGNADO', $activo->fresh()->estado_registro);
        $this->assertSame(VerificacionFisica::ESTADO_PENDIENTE, $verificacion->estado_aprobacion);

        // Y la solicitud aparece en la bandeja de aprobación.
        $this->actingAs($this->admin)
            ->get(route('verificaciones-aprobacion.index'))
            ->assertOk()
            ->assertSee($activo->codigo_activo);
    }
}
