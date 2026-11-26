<?php

namespace Tests\Feature;

use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El sistema tiene que abrir sin inventario: es como arranca la primera vez,
 * con usuarios, carreras, ambientes e ítems, pero todavía sin activos.
 */
class SistemaVacioTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $custodio;

    protected Ambiente $ambiente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $carrera = Carrera::create(['codigo' => 'SIF', 'nombre' => 'Sistemas Informáticos']);

        $this->ambiente = Ambiente::create([
            'codigo' => 'LAB-3',
            'nombre' => 'Laboratorio 3',
            'id_carrera' => $carrera->id_carrera,
        ]);

        Categoria::create(['nombre' => 'Equipos']);
        FuenteFinanciamiento::create(['codigo' => 'ADQ', 'nombre' => 'Adquisición']);

        $this->custodio = User::factory()->custodio()->create(['id_carrera' => $carrera->id_carrera]);

        $item = Item::create([
            'numero_item' => '131',
            'descripcion' => 'Docente Custodio - Laboratorio 3',
            'unidad' => 'Laboratorio 3',
            'id_carrera' => $carrera->id_carrera,
            'id_ambiente' => $this->ambiente->id_ambiente,
        ]);

        $item->custodios()->sync([$this->custodio->id_usuario]);

        $this->assertSame(0, ActivoFijo::count());
    }

    public function test_el_tablero_abre_sin_activos(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
    }

    public function test_el_inventario_abre_sin_activos(): void
    {
        $this->actingAs($this->admin)->get(route('activos.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('activos.create'))->assertOk();
    }

    public function test_los_reportes_abren_sin_activos(): void
    {
        $this->actingAs($this->admin)->get(route('reportes.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('reportes.inventario.general'))->assertOk();
        $this->actingAs($this->admin)
            ->get(route('reportes.por.ambiente', $this->ambiente->id_ambiente))
            ->assertOk();
    }

    public function test_las_actas_abren_sin_activos(): void
    {
        $this->actingAs($this->admin)->get(route('actas.transferencia.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('actas.baja.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('actas.liberacion.index'))->assertOk();
    }

    public function test_el_ambiente_se_ve_bien_sin_activos(): void
    {
        $this->actingAs($this->admin)
            ->get(route('ambientes.detalle', $this->ambiente->id_ambiente))
            ->assertOk();

        $this->actingAs($this->custodio)
            ->get(route('ambientes.detalle', $this->ambiente->id_ambiente))
            ->assertOk();
    }
}
