<?php

namespace Tests\Feature;

use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ImportarActivosTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_download_includes_manual_ids_and_active_custodians_by_career(): void
    {
        $carrera = Carrera::create([
            'codigo' => 'TEST-SIF',
            'nombre' => 'Sistemas Informáticos',
        ]);
        $ambiente = Ambiente::create([
            'codigo' => 'LAB-IMPORT',
            'nombre' => 'Laboratorio de importación',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $otraCarrera = Carrera::create([
            'codigo' => 'TEST-INA',
            'nombre' => 'Industrias de Alimentos',
        ]);
        $otroAmbiente = Ambiente::create([
            'codigo' => 'LAB-OTRO',
            'nombre' => 'Laboratorio de otra carrera',
            'id_carrera' => $otraCarrera->id_carrera,
        ]);
        $categoria = Categoria::create(['nombre' => 'Categoría de importación']);
        $categoriaOtraCarrera = Categoria::create([
            'nombre' => 'Categoría propia de otra carrera',
            'id_ambiente' => $otroAmbiente->id_ambiente,
        ]);
        $fuente = FuenteFinanciamiento::create([
            'codigo' => 'ADQ',
            'nombre' => 'Adquisición',
        ]);
        $custodio = User::factory()->custodio()->create([
            'ci' => '00012345',
            'nombre_completo' => 'Custodio Activo',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $custodioInactivo = User::factory()->custodio()->inactivo()->create([
            'ci' => '00054321',
            'id_carrera' => $carrera->id_carrera,
        ]);
        User::factory()->custodio()->create([
            'ci' => '00067890',
            'nombre_completo' => 'Custodio sin carrera',
            'id_carrera' => null,
        ]);
        $admin = User::factory()->admin()->create(['ci' => '00000001']);

        $response = $this->actingAs($admin)
            ->get(route('activos.importar.template'))
            ->assertDownload('plantilla_de_activos.xlsx');

        $archivoTemporal = tmpfile();
        fwrite($archivoTemporal, $response->streamedContent());
        $rutaTemporal = stream_get_meta_data($archivoTemporal)['uri'];
        $spreadsheet = IOFactory::createReader('Xlsx')->load($rutaTemporal);

        try {
            $this->assertSame(['Plantilla Activos', 'Manual e IDs'], $spreadsheet->getSheetNames());

            $hojaManual = $spreadsheet->getSheetByName('Manual e IDs');
            $valoresManual = [];
            $filasManual = $hojaManual->toArray();
            foreach ($filasManual as $fila) {
                array_push($valoresManual, ...$fila);
            }
            $valoresTexto = array_map('strval', $valoresManual);
            $filaCarrera = collect($filasManual)
                ->search(fn (array $fila): bool => $fila[0] === "Carrera {$carrera->id_carrera} - {$carrera->nombre}");
            $filaOtraCarrera = collect($filasManual)
                ->search(fn (array $fila): bool => $fila[0] === "Carrera {$otraCarrera->id_carrera} - {$otraCarrera->nombre}");
            $filaCategorias = collect($filasManual)
                ->search(fn (array $fila): bool => $fila[0] === 'ID CATEGORÍA');

            $this->assertContains('ci_custodio', $spreadsheet->getSheetByName('Plantilla Activos')->toArray()[0]);
            $this->assertContains('estado_fisico', $spreadsheet->getSheetByName('Plantilla Activos')->toArray()[0]);
            $this->assertContains("Carrera {$carrera->id_carrera} - {$carrera->nombre}", $valoresManual);
            $this->assertIsInt($filaCarrera);
            $this->assertIsInt($filaOtraCarrera);
            $this->assertIsInt($filaCategorias);
            $this->assertSame('CATEGORÍA', $hojaManual->getCell('B'.($filaCategorias + 1))->getValue());
            $this->assertSame((string) $categoria->id_categoria, (string) $hojaManual->getCell('A'.($filaCategorias + 2))->getValue());
            $this->assertSame((string) $categoriaOtraCarrera->id_categoria, (string) $hojaManual->getCell('A'.($filaCategorias + 3))->getValue());
            $this->assertContains((string) $ambiente->id_ambiente, $valoresTexto);
            $this->assertContains((string) $otroAmbiente->id_ambiente, $valoresTexto);
            $this->assertContains((string) $categoria->id_categoria, $valoresTexto);
            $this->assertContains((string) $fuente->id_fuente, $valoresTexto);
            $this->assertContains('00012345', $valoresTexto);
            $this->assertContains('00067890', $valoresTexto);
            $this->assertNotContains('00054321', $valoresTexto);
            $this->assertNotContains('00000001', $valoresTexto);
            $this->assertSame('Bueno', $hojaManual->getCell('D5')->getValue());
            $this->assertSame('B', $hojaManual->getCell('E5')->getValue());
            $this->assertSame('Regular', $hojaManual->getCell('D6')->getValue());
            $this->assertSame('R', $hojaManual->getCell('E6')->getValue());
            $this->assertSame('Malo', $hojaManual->getCell('D7')->getValue());
            $this->assertSame('M', $hojaManual->getCell('E7')->getValue());
            $this->assertSame('Fuera de funcionamiento', $hojaManual->getCell('D8')->getValue());
            $this->assertSame('FF', $hojaManual->getCell('E8')->getValue());
            $colorCarrera = $hojaManual->getStyle('A'.($filaCarrera + 1))->getFill()->getStartColor()->getRGB();
            $colorOtraCarrera = $hojaManual->getStyle('A'.($filaOtraCarrera + 1))->getFill()->getStartColor()->getRGB();
            $this->assertNotSame($colorCarrera, $colorOtraCarrera);
            $this->assertSame('G', $hojaManual->getHighestColumn());
            $this->assertLessThanOrEqual(34, $hojaManual->getColumnDimension('B')->getWidth());
            $this->assertLessThanOrEqual(30, $hojaManual->getColumnDimension('E')->getWidth());
        } finally {
            $spreadsheet->disconnectWorksheets();
            fclose($archivoTemporal);
        }
    }
}
