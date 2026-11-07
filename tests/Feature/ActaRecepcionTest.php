<?php

namespace Tests\Feature;

use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ActaRecepcionTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_asset_opens_observation_form_without_using_saved_asset_observations(): void
    {
        $context = $this->createInventoryContext();

        $this->actingAs($context['admin'])
            ->get(route('actas.recepcion.pdf', $context['activo']->id_activo))
            ->assertOk()
            ->assertSee('Observaciones del acta de recepción')
            ->assertSee('name="observaciones"', false)
            ->assertSee('OBS-TEST-001')
            ->assertDontSee('Observación histórica del activo');
    }

    public function test_legacy_single_pdf_route_redirects_to_observation_form(): void
    {
        $context = $this->createInventoryContext();

        $this->actingAs($context['admin'])
            ->get(route('actas.recepcion.activo.pdf', $context['activo']->id_activo))
            ->assertRedirect(route('actas.recepcion.pdf', $context['activo']->id_activo));
    }

    public function test_multiple_assets_open_observation_form_with_selected_assets(): void
    {
        $context = $this->createInventoryContext();

        $this->actingAs($context['admin'])
            ->get(route('actas.recepcion.pdf.multiple', [
                'ids' => [$context['activo']->id_activo],
            ]))
            ->assertOk()
            ->assertSee('Activos seleccionados (1)')
            ->assertSee('OBS-TEST-001')
            ->assertSee('name="observaciones"', false)
            ->assertSee('action="'.route('actas.recepcion.pdf.multiple.generate', [
                'ids' => [$context['activo']->id_activo],
            ]).'"', false);
    }

    public function test_batch_pdf_uses_configured_text_and_manual_observations(): void
    {
        $context = $this->createInventoryContext();
        $this->actingAs($context['admin']);
        $this->post(route('configuracion.texto-cumplimiento'), [
            'texto_cumplimiento' => 'Cumplimiento configurado',
        ])->assertSessionHas('success');
        $this->post(route('configuracion.texto-legal'), [
            'texto_legal' => 'Texto legal configurado',
        ])->assertSessionHas('success');

        $activos = ActivoFijo::with(['categoria', 'ambiente.carrera', 'fuente', 'custodio'])
            ->whereKey($context['activo']->id_activo)
            ->get();
        $html = view('actas.recepcion.batch', [
            'activos' => $activos,
            'observaciones' => 'Revisado <sin daños>',
        ])->render();

        $this->assertStringContainsString('Cumplimiento configurado', $html);
        $this->assertStringContainsString('Texto legal configurado', $html);
        $this->assertStringContainsString('Revisado &lt;sin daños&gt;', $html);
        $this->assertStringNotContainsString('ASIGNACION PROVISIONAL', $html);
        $this->assertStringNotContainsString('Observación histórica del activo', $html);
    }

    public function test_blank_observation_is_rendered_as_sin_observaciones(): void
    {
        $context = $this->createInventoryContext();
        $this->actingAs($context['admin']);
        $this->post(route('configuracion.texto-cumplimiento'), [
            'texto_cumplimiento' => '',
        ]);
        $this->post(route('configuracion.texto-legal'), [
            'texto_legal' => '',
        ]);

        $activos = ActivoFijo::with(['categoria', 'ambiente.carrera', 'fuente', 'custodio'])
            ->whereKey($context['activo']->id_activo)
            ->get();
        $html = view('actas.recepcion.batch', [
            'activos' => $activos,
            'observaciones' => '',
        ])->render();

        $this->assertStringContainsString('Sin observaciones', $html);
        $this->assertStringContainsString('En cumplimiento al D.S. 0181', $html);
        $this->assertStringContainsString('IMPORTANTE:', $html);
        $this->assertStringNotContainsString('ASIGNACION PROVISIONAL', $html);
    }

    public function test_batch_pdf_receives_observation_without_changing_asset_record(): void
    {
        $context = $this->createInventoryContext();
        $pdf = Mockery::mock(DomPdf::class);
        $pdf->shouldReceive('setPaper')->once()->andReturnSelf();
        $pdf->shouldReceive('setOption')->once()->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(
            response('test pdf', 200, ['Content-Type' => 'application/pdf'])
        );

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('actas.recepcion.batch', Mockery::on(function (array $data): bool {
                return $data['observaciones'] === 'Revisión realizada por el responsable.';
            }))
            ->andReturn($pdf);

        $response = $this->actingAs($context['admin'])
            ->post(route('actas.recepcion.pdf.multiple.generate', [
                'ids' => [$context['activo']->id_activo],
            ]), [
                'observaciones' => 'Revisión realizada por el responsable.',
            ]);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertDatabaseHas('activos_fijos', [
            'id_activo' => $context['activo']->id_activo,
            'observaciones' => 'Observación histórica del activo',
        ]);
    }

    public function test_single_pdf_receives_manual_observation(): void
    {
        $context = $this->createInventoryContext();
        $pdf = Mockery::mock(DomPdf::class);
        $pdf->shouldReceive('setPaper')->once()->andReturnSelf();
        $pdf->shouldReceive('setOption')->once()->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(
            response('test pdf', 200, ['Content-Type' => 'application/pdf'])
        );

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('actas.recepcion.pdf', Mockery::on(function (array $data): bool {
                return $data['observaciones'] === 'Se entrega en buen estado.';
            }))
            ->andReturn($pdf);

        $this->actingAs($context['admin'])
            ->post(route('actas.recepcion.pdf.generate', $context['activo']->id_activo), [
                'observaciones' => 'Se entrega en buen estado.',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_batch_pdf_rejects_observations_longer_than_5000_characters(): void
    {
        $context = $this->createInventoryContext();

        $this->actingAs($context['admin'])
            ->post(route('actas.recepcion.pdf.multiple.generate', [
                'ids' => [$context['activo']->id_activo],
            ]), [
                'observaciones' => str_repeat('a', 5001),
            ])
            ->assertSessionHasErrors('observaciones');
    }

    /**
     * @return array{admin: User, activo: ActivoFijo}
     */
    private function createInventoryContext(): array
    {
        $carrera = Carrera::create([
            'codigo' => 'SIF',
            'nombre' => 'Sistemas Informáticos',
        ]);

        $ambiente = Ambiente::create([
            'codigo' => 'LAB-ACTA',
            'nombre' => 'Laboratorio de actas',
            'id_carrera' => $carrera->id_carrera,
        ]);

        $categoria = Categoria::create([
            'codigo' => 'EQP-ACTA',
            'nombre' => 'Equipos de prueba',
            'id_ambiente' => $ambiente->id_ambiente,
        ]);

        $fuente = FuenteFinanciamiento::create([
            'codigo' => 'ADQ',
            'nombre' => 'Adquisición de prueba',
        ]);

        $admin = User::factory()->admin()->create([
            'id_carrera' => $carrera->id_carrera,
        ]);

        $activo = ActivoFijo::create([
            'codigo_activo' => 'OBS-TEST-001',
            'tipo_bien' => 'NO_ACTIVO',
            'nombre' => 'Activo de prueba',
            'descripcion' => 'Activo para pruebas de acta',
            'observaciones' => 'Observación histórica del activo',
            'id_categoria' => $categoria->id_categoria,
            'id_ambiente' => $ambiente->id_ambiente,
            'id_fuente' => $fuente->id_fuente,
            'id_custodio' => $admin->id_usuario,
        ]);

        return compact('admin', 'activo');
    }
}
