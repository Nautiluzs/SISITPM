<?php

namespace Tests\Feature;

use App\Models\ActivoFijo;
use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_por_aula_se_puede_imprimir_y_aparece_en_el_historial(): void
    {
        ['admin' => $admin, 'ambiente' => $ambiente] = $this->crearInventario();

        $respuesta = $this->actingAs($admin)
            ->get(route('reportes.aula.excel', $ambiente->id_ambiente));

        $respuesta->assertOk()
            ->assertSee('INVENTARIO POR AULA', false)
            ->assertSee('CÓDIGO', false)
            ->assertSee('ESTADO', false)
            ->assertSee('TPM-REP-01');

        $this->assertSame(
            'application/vnd.ms-excel; charset=UTF-8',
            $respuesta->headers->get('Content-Type')
        );
        $this->assertSame("\xEF\xBB\xBF", substr($respuesta->getContent(), 0, 3));
        $this->assertDatabaseHas('historial_reportes', [
            'id_usuario' => $admin->id_usuario,
            'tipo_reporte' => 'Inventario por aula',
            'formato' => 'EXCEL',
            'nombre_archivo' => 'Inventario_Aula_Laboratorio_3_'.now()->format('Y-m-d').'.xls',
        ]);

        $this->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Inventario por aula')
            ->assertSee('Inventario_Aula_Laboratorio_3_'.now()->format('Y-m-d').'.xls');
    }

    public function test_excel_de_activos_usa_la_plantilla_completa_y_se_guarda_en_el_historial(): void
    {
        ['admin' => $admin, 'ambiente' => $ambiente] = $this->crearInventario();

        $respuesta = $this->actingAs($admin)
            ->get(route('activos.excel', ['id_ambiente' => $ambiente->id_ambiente]));

        $respuesta->assertOk()
            ->assertSee('INVENTARIO POR AMBIENTE', false)
            ->assertSee('TPM-REP-01');

        $this->assertSame("\xEF\xBB\xBF", substr($respuesta->getContent(), 0, 3));
        $this->assertDatabaseHas('historial_reportes', [
            'id_usuario' => $admin->id_usuario,
            'tipo_reporte' => 'Inventario de activos',
            'formato' => 'EXCEL',
        ]);
    }

    /**
     * @return array{admin: User, ambiente: Ambiente}
     */
    private function crearInventario(): array
    {
        $carrera = Carrera::create(['nombre' => 'Sistemas Informáticos', 'codigo' => 'SIF']);
        $ambiente = Ambiente::create([
            'nombre' => 'Laboratorio 3',
            'codigo' => 'LAB-3',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $admin = User::factory()->admin()->create();
        $categoria = Categoria::create([
            'nombre' => 'Equipos',
            'id_ambiente' => $ambiente->id_ambiente,
        ]);
        $fuente = FuenteFinanciamiento::create([
            'nombre' => 'Tesorería',
            'codigo' => 'TES',
        ]);

        ActivoFijo::create([
            'codigo_activo' => 'TPM-REP-01',
            'nombre' => 'Computadora',
            'descripcion' => 'Equipo de prueba',
            'marca' => 'Dell',
            'modelo' => 'OptiPlex',
            'numero_serie' => 'REP-0001',
            'fecha_adquisicion' => '2025-01-15',
            'valor_adquisicion' => 3500,
            'id_categoria' => $categoria->id_categoria,
            'id_ambiente' => $ambiente->id_ambiente,
            'id_fuente' => $fuente->id_fuente,
            'id_custodio' => $admin->id_usuario,
            'tipo_bien' => 'ACTIVO_FIJO',
            'estado_fisico' => 'B',
            'estado_registro' => 'ASIGNADO',
        ]);

        return compact('admin', 'ambiente');
    }
}
