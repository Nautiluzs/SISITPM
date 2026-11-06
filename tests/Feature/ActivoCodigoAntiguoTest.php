<?php

namespace Tests\Feature;

use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivoCodigoAntiguoTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_shows_optional_old_code_with_or_without_prefilled_context(): void
    {
        $context = $this->createInventoryContext();

        $this->actingAs($context['admin'])
            ->get(route('activos.create'))
            ->assertOk()
            ->assertSee('name="codigo_antiguo"', false)
            ->assertSee('Código antiguo (opcional)');

        $this->get(route('activos.create', [
            'id_ambiente' => $context['ambiente']->id_ambiente,
            'id_categoria' => $context['categoria']->id_categoria,
        ]))
            ->assertOk()
            ->assertSee('name="codigo_antiguo"', false);
    }

    public function test_asset_can_be_created_without_an_old_code(): void
    {
        Storage::fake('public');
        $context = $this->createInventoryContext();

        $response = $this->actingAs($context['admin'])
            ->post(route('activos.store'), $this->validAssetPayload($context, 'ACT-TEST-001'));

        $response->assertRedirect(route('categorias.show', $context['categoria']->id_categoria));

        $activo = ActivoFijo::where('codigo_activo', 'ACT-TEST-001')->firstOrFail();

        $this->assertDatabaseHas('activos_fijos', [
            'id_activo' => $activo->id_activo,
            'codigo_antiguo' => null,
        ]);
        Storage::disk('public')->assertExists($activo->ruta_qr);
    }

    public function test_asset_can_be_searched_by_its_old_code(): void
    {
        Storage::fake('public');
        $context = $this->createInventoryContext();
        $payload = $this->validAssetPayload($context, 'ACT-TEST-002');
        $payload['codigo_antiguo'] = 'OLD-INV-042';

        $this->actingAs($context['admin'])
            ->post(route('activos.store'), $payload)
            ->assertRedirect(route('categorias.show', $context['categoria']->id_categoria));

        $activo = ActivoFijo::where('codigo_activo', 'ACT-TEST-002')->firstOrFail();

        $this->assertDatabaseHas('activos_fijos', [
            'id_activo' => $activo->id_activo,
            'codigo_antiguo' => 'OLD-INV-042',
        ]);

        $this->get(route('activos.index', ['buscar' => 'OLD-INV-042']))
            ->assertOk()
            ->assertSee('ACT-TEST-002')
            ->assertSee('Código antiguo: OLD-INV-042');

        $this->get(route('categorias.show', $context['categoria']->id_categoria))
            ->assertOk()
            ->assertSee('Código antiguo: OLD-INV-042');
    }

    /**
     * @return array{admin: User, ambiente: Ambiente, categoria: Categoria, fuente: FuenteFinanciamiento, custodio: User}
     */
    private function createInventoryContext(): array
    {
        $carrera = Carrera::create([
            'codigo' => 'SIF',
            'nombre' => 'Sistemas Informáticos',
        ]);

        $ambiente = Ambiente::create([
            'codigo' => 'LAB-OLD',
            'nombre' => 'Laboratorio de pruebas',
            'id_carrera' => $carrera->id_carrera,
        ]);

        $categoria = Categoria::create([
            'codigo' => 'EQP',
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

        $custodio = User::factory()->custodio()->create([
            'id_carrera' => $carrera->id_carrera,
        ]);

        return compact('admin', 'ambiente', 'categoria', 'fuente', 'custodio');
    }

    /**
     * @param  array{ambiente: Ambiente, categoria: Categoria, fuente: FuenteFinanciamiento, custodio: User}  $context
     * @return array<string, int|string>
     */
    private function validAssetPayload(array $context, string $codigoActivo): array
    {
        return [
            'codigo_activo' => $codigoActivo,
            'tipo_bien' => 'NO_ACTIVO',
            'nombre' => 'Equipo de prueba',
            'descripcion' => 'Activo de prueba',
            'id_ambiente' => $context['ambiente']->id_ambiente,
            'id_categoria' => $context['categoria']->id_categoria,
            'id_fuente' => $context['fuente']->id_fuente,
            'id_custodio' => $context['custodio']->id_usuario,
            'estado_fisico' => 'B',
            'fecha_adquisicion' => '2025-01-15',
            'valor_adquisicion' => '0',
        ];
    }
}
