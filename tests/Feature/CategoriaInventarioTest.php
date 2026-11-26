<?php

namespace Tests\Feature;

use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CategoriaInventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_form_uses_shared_categories_independent_of_environment(): void
    {
        $this->seed(DatabaseSeeder::class);

        $carrera = Carrera::create([
            'codigo' => 'TEST-SIF',
            'nombre' => 'Sistemas Informáticos',
        ]);
        $ambienteFormulario = Ambiente::create([
            'codigo' => 'LAB-A',
            'nombre' => 'Laboratorio A',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $otroAmbiente = Ambiente::create([
            'codigo' => 'LAB-B',
            'nombre' => 'Laboratorio B',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $categoriaCompartida = Categoria::create([
            'nombre' => 'Categoría compartida',
            'id_ambiente' => $otroAmbiente->id_ambiente,
        ]);
        $admin = User::factory()->admin()->create();

        $this->assertDatabaseHas('categorias', ['nombre' => 'EDIFICACIONES']);
        $categoriaEstandar = Categoria::where('nombre', 'EDIFICACIONES')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('activos.create', ['id_ambiente' => $ambienteFormulario->id_ambiente]))
            ->assertOk()
            ->assertSee('ID '.$categoriaEstandar->id_categoria.' - EDIFICACIONES')
            ->assertSee('ID '.$categoriaCompartida->id_categoria.' - Categoría compartida');
    }

    public function test_asset_create_and_edit_forms_order_categories_by_id(): void
    {
        $admin = User::factory()->admin()->create();
        $carrera = Carrera::create(['codigo' => 'ORD', 'nombre' => 'Orden']);
        $ambiente = Ambiente::create([
            'codigo' => 'ORD-1',
            'nombre' => 'Ambiente orden',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $categoriaPrimera = Categoria::create(['nombre' => 'Zeta']);
        $categoriaSegunda = Categoria::create(['nombre' => 'Alfa']);
        $fuente = FuenteFinanciamiento::create(['codigo' => 'ADQ', 'nombre' => 'Adquisición']);
        $custodio = User::factory()->custodio()->create();
        $activo = ActivoFijo::create([
            'codigo_activo' => 'ORD-ACT-1',
            'tipo_bien' => 'NO_ACTIVO',
            'nombre' => 'Activo de orden',
            'descripcion' => 'Activo para comprobar el orden de categorías.',
            'id_categoria' => $categoriaPrimera->id_categoria,
            'id_ambiente' => $ambiente->id_ambiente,
            'id_fuente' => $fuente->id_fuente,
            'id_custodio' => $custodio->id_usuario,
            'estado_fisico' => 'B',
            'estado_registro' => 'ASIGNADO',
        ]);
        $idsOrdenados = [$categoriaPrimera->id_categoria, $categoriaSegunda->id_categoria];

        $this->actingAs($admin)
            ->get(route('activos.create'))
            ->assertOk()
            ->assertViewHas('categorias', fn ($categorias) => $categorias->pluck('id_categoria')->all() === $idsOrdenados);

        $this->get(route('activos.edit', $activo->id_activo))
            ->assertOk()
            ->assertViewHas('categorias', fn ($categorias) => $categorias->pluck('id_categoria')->all() === $idsOrdenados);
    }

    public function test_admin_can_create_update_and_delete_a_global_category(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('categorias.store'), [
                'nombre' => 'Categoría personalizada',
            ])
            ->assertRedirect(route('categorias.index'));

        $categoria = Categoria::where('nombre', 'Categoría personalizada')->firstOrFail();
        $this->assertDatabaseHas('categorias', [
            'id_categoria' => $categoria->id_categoria,
            'id_ambiente' => null,
        ]);

        $this->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('ID '.$categoria->id_categoria)
            ->assertDontSee('name="codigo"', false)
            ->assertDontSee('name="descripcion"', false);
        $this->get(route('categorias.create'))
            ->assertOk()
            ->assertSee('name="nombre"', false)
            ->assertDontSee('name="codigo"', false)
            ->assertDontSee('name="descripcion"', false);
        $this->get(route('categorias.edit', $categoria->id_categoria))
            ->assertOk()
            ->assertSee('name="nombre"', false)
            ->assertDontSee('name="codigo"', false)
            ->assertDontSee('name="descripcion"', false);

        $this->put(route('categorias.update', $categoria->id_categoria), [
            'nombre' => 'Categoría editada',
        ])->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'id_categoria' => $categoria->id_categoria,
            'nombre' => 'Categoría editada',
            'id_ambiente' => null,
        ]);

        $this->delete(route('categorias.destroy', $categoria->id_categoria))
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseMissing('categorias', ['id_categoria' => $categoria->id_categoria]);
    }

    public function test_category_schema_no_longer_has_code_or_description_columns(): void
    {
        $this->assertFalse(Schema::hasColumn('categorias', 'codigo'));
        $this->assertFalse(Schema::hasColumn('categorias', 'descripcion'));
    }

    public function test_non_admin_cannot_open_category_create_or_edit_pages(): void
    {
        $custodio = User::factory()->custodio()->create();
        $categoria = Categoria::create(['nombre' => 'Categoría protegida']);

        $this->actingAs($custodio)
            ->get(route('categorias.create'))
            ->assertForbidden();

        $this->get(route('categorias.edit', $categoria->id_categoria))
            ->assertForbidden();
    }

    public function test_environment_lists_assets_and_filters_only_categories_with_current_assets(): void
    {
        Storage::fake('public');

        $carrera = Carrera::create([
            'codigo' => 'INA',
            'nombre' => 'Industrias de Alimentos',
        ]);
        $ambiente = Ambiente::create([
            'codigo' => 'LAB-CAT-A',
            'nombre' => 'Laboratorio de categorías',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $otroAmbiente = Ambiente::create([
            'codigo' => 'LAB-CAT-B',
            'nombre' => 'Otro laboratorio',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $ambienteVacio = Ambiente::create([
            'codigo' => 'LAB-CAT-VACIO',
            'nombre' => 'Ambiente sin activos',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $fuente = FuenteFinanciamiento::create([
            'codigo' => 'ADQ',
            'nombre' => 'Adquisición',
        ]);
        $custodio = User::factory()->custodio()->create([
            'id_carrera' => $carrera->id_carrera,
        ]);
        $admin = User::factory()->admin()->create();
        $categoriaUno = Categoria::create(['nombre' => 'Categoría uno']);
        $categoriaDos = Categoria::create(['nombre' => 'Categoría dos']);
        $categoriaBaja = Categoria::create(['nombre' => 'Categoría con activos dados de baja']);
        $categoriaVacia = Categoria::create(['nombre' => 'Categoría sin activos']);

        $this->actingAs($admin);

        $this->createAsset($ambiente, $categoriaUno, $fuente, $custodio, 'ENV-A-001')
            ->assertRedirect(route('ambientes.show', $ambiente->id_ambiente));
        $this->createAsset($ambiente, $categoriaDos, $fuente, $custodio, 'ENV-A-002');
        $this->createAsset($ambiente, $categoriaBaja, $fuente, $custodio, 'ENV-A-003');
        $this->createAsset($otroAmbiente, $categoriaUno, $fuente, $custodio, 'ENV-B-001');
        ActivoFijo::where('codigo_activo', 'ENV-A-003')->update(['estado_registro' => 'BAJA']);

        $this->get(route('ambientes.show', $ambiente->id_ambiente))
            ->assertOk()
            ->assertSee('Activos individuales (2)')
            ->assertSee('Imprimir seleccionados')
            ->assertSee('Actas de recepción')
            ->assertSee('Imprimir todas')
            ->assertSee('Eliminar seleccionados')
            ->assertSee('ENV-A-001')
            ->assertSee('ENV-A-002')
            ->assertDontSee('ENV-B-001')
            ->assertDontSee('value="'.$categoriaBaja->id_categoria.'"', false)
            ->assertDontSee('value="'.$categoriaVacia->id_categoria.'"', false);

        $this->get(route('ambientes.show', [
            'id' => $ambiente->id_ambiente,
            'id_categoria' => $categoriaUno->id_categoria,
        ]))
            ->assertOk()
            ->assertSee('ENV-A-001')
            ->assertDontSee('ENV-A-002')
            ->assertDontSee('ENV-B-001');

        $this->get(route('ambientes.show', $ambienteVacio->id_ambiente))
            ->assertOk()
            ->assertDontSee('Filtrar por categoría');

        $this->delete(route('categorias.destroy', $categoriaUno->id_categoria))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categorias', ['id_categoria' => $categoriaUno->id_categoria]);
    }

    private function createAsset(
        Ambiente $ambiente,
        Categoria $categoria,
        FuenteFinanciamiento $fuente,
        User $custodio,
        string $codigo
    ): TestResponse {
        return $this->post(route('activos.store'), [
            'codigo_activo' => $codigo,
            'tipo_bien' => 'NO_ACTIVO',
            'nombre' => 'Activo de prueba',
            'descripcion' => 'Activo de prueba para filtro',
            'id_ambiente' => $ambiente->id_ambiente,
            'id_categoria' => $categoria->id_categoria,
            'id_fuente' => $fuente->id_fuente,
            'id_custodio' => $custodio->id_usuario,
            'estado_fisico' => 'B',
            'fecha_adquisicion' => '2025-01-15',
            'valor_adquisicion' => '0',
        ]);
    }
}
