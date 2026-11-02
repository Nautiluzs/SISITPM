<?php

namespace Database\Seeders;

use App\Models\Ambiente;
use App\Models\Carrera;
use App\Models\Categoria;
use App\Models\FuenteFinanciamiento;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder as BaseSeeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends BaseSeeder
{
    public function run(): void
    {
        try {
            $this->runSeeder();
        } catch (\Throwable $e) {
            $this->command->warn('⚠️ Seeder saltado por error: '.$e->getMessage());
        }
    }

    private function runSeeder(): void
    {

        // ============================================================
        // 1. CARRERAS
        // ============================================================
        $carreras = [
            ['codigo' => 'MEI', 'nombre' => 'Mecánica Industrial'],
            ['codigo' => 'MAZ', 'nombre' => 'Mecánica Automotriz'],
            ['codigo' => 'INA', 'nombre' => 'Industrias de Alimentos'],
            ['codigo' => 'SIF', 'nombre' => 'Sistemas Informáticos'],
            ['codigo' => 'GTR', 'nombre' => 'Gastronomía'],
            ['codigo' => 'ELC', 'nombre' => 'Electrónica'],
            ['codigo' => 'AGRO', 'nombre' => 'Agropecuaria'],
            ['codigo' => 'ADM', 'nombre' => 'Unidades Administrativas'],
        ];

        $carreraIds = [];
        foreach ($carreras as $carrera) {
            $fila = Carrera::updateOrCreate(['codigo' => $carrera['codigo']], $carrera);
            $carreraIds[$carrera['codigo']] = $fila->id_carrera;
        }

        $sifId = $carreraIds['SIF'];
        $inaId = $carreraIds['INA'];
        $gtrId = $carreraIds['GTR'];
        $mazId = $carreraIds['MAZ'];
        $meiId = $carreraIds['MEI'];
        $elcId = $carreraIds['ELC'];
        $agroId = $carreraIds['AGRO'];
        $admId = $carreraIds['ADM'];

        // ============================================================
        // 2. PERSONAL (Parte Mensual Junio 2025 - I.T. "Puerto de Mejillones")
        // ============================================================
        $personal = [

            // ---------------- AUTORIDADES ----------------
            [
                'ci' => '4960313', 'nombre_completo' => 'Jimmy Ovidio Sirpa Choque',
                'email' => 'rector@itpm.edu.bo', 'password' => Hash::make('rector123'),
                'cargo' => 'Rector', 'unidad' => 'Rectorado', 'rol' => 'RECTOR',
                'estado' => 'ACTIVO', 'id_carrera' => null, 'puede_verificar' => false,
                'items' => ['88'],
            ],
            [
                'ci' => '2383715', 'nombre_completo' => 'Ana Maria Alvarez Tellez',
                'email' => 'ana.alvarez@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Directora Académica', 'unidad' => 'Dirección Académica', 'rol' => 'ADMINISTRADOR',
                'estado' => 'ACTIVO', 'id_carrera' => null, 'puede_verificar' => true,
                'items' => ['87'],
            ],
            [
                'ci' => '4756925', 'nombre_completo' => 'Ana Lía Zapana Cortez',
                'email' => 'admin@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Directora Administrativa', 'unidad' => 'Dirección Administrativa', 'rol' => 'ADMINISTRADOR',
                'estado' => 'ACTIVO', 'id_carrera' => null, 'puede_verificar' => true,
                'items' => ['91'],
            ],
            [
                'ci' => '7081338', 'nombre_completo' => 'Calixto Aro Mamani',
                'email' => 'calixto.aro@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Director Técnico', 'unidad' => 'Dirección Técnica', 'rol' => 'ADMINISTRADOR',
                'estado' => 'ACTIVO', 'id_carrera' => null, 'puede_verificar' => true,
                'items' => ['107'],
            ],

            // ---------------- JEFES DE CARRERA ----------------
            [
                'ci' => '4837535', 'nombre_completo' => 'Juan Ruben Paco Laura',
                'email' => 'juan.paco@itpm.edu.bo', 'password' => Hash::make('jefe123'),
                'cargo' => 'Jefe de Carrera de Mecánica Industrial', 'unidad' => 'Escuela de Mecánica Industrial',
                'rol' => 'JEFE_CARRERA', 'estado' => 'ACTIVO', 'id_carrera' => $meiId,
                'puede_verificar' => false, 'items' => ['108'],
            ],
            [
                'ci' => '3530981', 'nombre_completo' => 'Eduardo Alejo Quispe',
                'email' => 'eduardo.alejo@itpm.edu.bo', 'password' => Hash::make('jefe123'),
                'cargo' => 'Jefe de Carrera de Mecánica Automotriz', 'unidad' => 'Escuela de Mecánica Automotriz',
                'rol' => 'JEFE_CARRERA', 'estado' => 'ACTIVO', 'id_carrera' => $mazId,
                'puede_verificar' => false, 'items' => ['116'],
            ],
            [
                'ci' => '4286280', 'nombre_completo' => 'Eliza Nina Coronel',
                'email' => 'eliza.nina@itpm.edu.bo', 'password' => Hash::make('jefe123'),
                'cargo' => 'Jefe de Carrera de Sistemas Informáticos', 'unidad' => 'Escuela de Sistemas Informáticos',
                'rol' => 'JEFE_CARRERA', 'estado' => 'ACTIVO', 'id_carrera' => $sifId,
                'puede_verificar' => false, 'items' => ['124'],
            ],
            [
                'ci' => '9226894', 'nombre_completo' => 'Remedios Valeria Mamani Mamani',
                'email' => 'remedios.mamani@itpm.edu.bo', 'password' => Hash::make('jefe123'),
                'cargo' => 'Jefe de Carrera de Industria de Alimentos', 'unidad' => 'Escuela de Industria de Alimentos',
                'rol' => 'JEFE_CARRERA', 'estado' => 'ACTIVO', 'id_carrera' => $inaId,
                'puede_verificar' => false, 'items' => ['125'],
            ],
            [
                'ci' => '6906141', 'nombre_completo' => 'Guido Salinas Salvatierra',
                'email' => 'guido.salinas@itpm.edu.bo', 'password' => Hash::make('jefe123'),
                'cargo' => 'Jefe de Carrera de Gastronomía', 'unidad' => 'Escuela de Gastronomía',
                'rol' => 'JEFE_CARRERA', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId,
                'puede_verificar' => false, 'items' => ['635'],
            ],
            [
                'ci' => '3420178', 'nombre_completo' => 'Carlos Ruben Calderon Gutierrez',
                'email' => 'carlos.calderon@itpm.edu.bo', 'password' => Hash::make('jefe123'),
                'cargo' => 'Coordinador de Carrera de Electrónica', 'unidad' => 'Escuela de Electrónica',
                'rol' => 'JEFE_CARRERA', 'estado' => 'ACTIVO', 'id_carrera' => $elcId,
                'puede_verificar' => false, 'items' => ['143'],
            ],
            [
                'ci' => '3430003', 'nombre_completo' => 'Antonio Manuel Tarqui',
                'email' => 'antonio.tarqui@itpm.edu.bo', 'password' => Hash::make('jefe123'),
                'cargo' => 'Jefatura Sub Sede Corpa', 'unidad' => 'Sub Sede Corpa',
                'rol' => 'JEFE_CARRERA', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => false, 'items' => ['95'],
            ],

            // ---------------- ADMINISTRATIVOS / APOYO ----------------
            [
                'ci' => '6729549', 'nombre_completo' => 'Karla Rosse Mary Rada Irigoyen',
                'email' => 'karla.rada@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Secretaria de Rectorado', 'unidad' => 'Rectorado',
                'rol' => 'ADMINISTRATIVO', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => false, 'items' => ['89'],
            ],
            [
                'ci' => '8367032', 'nombre_completo' => 'Victor Josue Huañapaco Peña',
                'email' => 'victor.huanapaco@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Personal de Apoyo', 'unidad' => 'Institucional',
                'rol' => 'ADMINISTRATIVO', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => false, 'items' => ['90'],
            ],
            [
                'ci' => '4243543', 'nombre_completo' => 'Juan Carlos Osco Mamani',
                'email' => 'inventariador@itpm.edu.bo', 'password' => Hash::make('inventario123'),
                'cargo' => 'Encargado de Almacén', 'unidad' => 'Departamento de Activos',
                'rol' => 'INVENTARIADOR', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => true, 'items' => ['92'],
            ],
            [
                'ci' => '3366701', 'nombre_completo' => 'Paulina Laura Zarsuri',
                'email' => 'paulina.laura@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Portera', 'unidad' => 'Institucional',
                'rol' => 'ADMINISTRATIVO', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => false, 'items' => ['93'],
            ],
            [
                'ci' => '6844443', 'nombre_completo' => 'Marco Antonio Apala Garcia',
                'email' => 'marco.apala@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Sereno', 'unidad' => 'Dirección Técnica',
                'rol' => 'ADMINISTRATIVO', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => false, 'items' => ['94'],
            ],
            [
                'ci' => '4967116', 'nombre_completo' => 'Vania Daniela Condori Marca',
                'email' => 'vania.condori@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Secretaria de Dirección Académica', 'unidad' => 'Dirección Académica',
                'rol' => 'ADMINISTRATIVO', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => false, 'items' => ['97'],
            ],
            [
                'ci' => '4985154', 'nombre_completo' => 'Sandra Alanoca Condori',
                'email' => 'sandra.alanoca@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Secretaria de Dirección Académica', 'unidad' => 'Dirección Académica',
                'rol' => 'ADMINISTRATIVO', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => false, 'items' => ['98'],
            ],
            [
                'ci' => '6027758', 'nombre_completo' => 'Veronica Mamani Perez',
                'email' => 'veronica.mamani@itpm.edu.bo', 'password' => Hash::make('admin123*'),
                'cargo' => 'Intérprete LSB', 'unidad' => 'Institucional',
                'rol' => 'ADMINISTRATIVO', 'estado' => 'ACTIVO', 'id_carrera' => null,
                'puede_verificar' => false, 'items' => ['1'],
            ],

            // ---------------- DOCENTES MEI ----------------
            ['ci' => '4775769', 'nombre_completo' => 'Juan Clemen Silvestre Estevez', 'email' => 'juan.silvestre@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['84']],
            ['ci' => '4284129', 'nombre_completo' => 'David Carlos Tapia Quispe', 'email' => 'david.tapia@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['117']],
            ['ci' => '3386253', 'nombre_completo' => 'Bernardo Alfredo Castillo Fernandez', 'email' => 'bernardo.castillo@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['118']],
            ['ci' => '667057',  'nombre_completo' => 'Wilfredo Alcon Cachi', 'email' => 'wilfredo.alcon@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['120']],
            ['ci' => '6194946', 'nombre_completo' => 'Ruddy Gutierrez Bilbao', 'email' => 'ruddy.gutierrez@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['132']],
            ['ci' => '8353913', 'nombre_completo' => 'Julio Edgar Quispe Quispe', 'email' => 'julio.quispe@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['134']],
            ['ci' => '2760750', 'nombre_completo' => 'Carlos Hernan Geronimo Eugenio', 'email' => 'carlos.geronimo@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['148']],
            ['ci' => '5943899', 'nombre_completo' => 'Monica del Carmen Aguilar Ticona', 'email' => 'monica.aguilar@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['154']],
            ['ci' => '2217153', 'nombre_completo' => 'Martin Pacifico Carita Valeriano', 'email' => 'martin.carita@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['158']],
            ['ci' => '6819476', 'nombre_completo' => 'Henry Vladimir Gutierrez Casas', 'email' => 'henry.gutierrez@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['159']],
            ['ci' => '6036218', 'nombre_completo' => 'Oscar Enrique Anti Javier', 'email' => 'oscar.anti@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['167']],
            ['ci' => '4820162', 'nombre_completo' => 'Edwin Yapu Machicado', 'email' => 'edwin.yapu@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Industrial', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $meiId, 'puede_verificar' => false, 'items' => ['632']],

            // ---------------- DOCENTES MAZ ----------------
            ['ci' => '5965479', 'nombre_completo' => 'Juan Choquehuanca Limachi', 'email' => 'juan.choquehuanca@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['85']],
            ['ci' => '6844203', 'nombre_completo' => 'Felix Quispe Quispe', 'email' => 'felix.quispe@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['86']],
            ['ci' => '3407306', 'nombre_completo' => 'Juana Wilma Velarde Linares', 'email' => 'juana.velarde@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['96']],
            ['ci' => '8447977', 'nombre_completo' => 'Jaime Apaza Quispe', 'email' => 'jaime.apaza@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['102']],
            ['ci' => '4984422', 'nombre_completo' => 'Raul Alberto Challco Vino', 'email' => 'raul.challco@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['105']],
            ['ci' => '2639811', 'nombre_completo' => 'Juan Ramiro Huanca Rodriguez', 'email' => 'juan.huanca@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['109']],
            ['ci' => '6155774', 'nombre_completo' => 'Adhemar Patty Copa', 'email' => 'adhemar.patty@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['112']],
            ['ci' => '7008543', 'nombre_completo' => 'Freddy Mamani Huacoto', 'email' => 'freddy.mamani@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['113']],
            ['ci' => '4983977', 'nombre_completo' => 'Ederson Willam Laura Mena', 'email' => 'ederson.laura@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['114']],
            ['ci' => '6001623', 'nombre_completo' => 'Johnny Jarandilla Aliaga', 'email' => 'johnny.jarandilla@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['119']],
            ['ci' => '3718643', 'nombre_completo' => 'Ruben Cerrogrande Quispe', 'email' => 'ruben.cerrogrande@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['121']],
            ['ci' => '6801382', 'nombre_completo' => 'Carlos Alfredo Peralta Quispe', 'email' => 'carlos.peralta@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['123']],
            ['ci' => '4888750', 'nombre_completo' => 'Elder Ramos Aliaga', 'email' => 'elder.ramos@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['128']],
            ['ci' => '6178380', 'nombre_completo' => 'Robert Jesus Cuevas Poma', 'email' => 'robert.cuevas@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['133']],
            ['ci' => '6725782', 'nombre_completo' => 'Reynaldo Huallpa Callisaya', 'email' => 'reynaldo.huallpa@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['135']],
            ['ci' => '4373581', 'nombre_completo' => 'Pedro Plata Gutierrez', 'email' => 'pedro.plata@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['140']],
            ['ci' => '4243175', 'nombre_completo' => 'Freddy Argollo Luna', 'email' => 'freddy.argollo@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['141']],
            ['ci' => '9186290', 'nombre_completo' => 'Franz Rene Apaza Machaca', 'email' => 'franz.apaza@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['142']],
            ['ci' => '4932029', 'nombre_completo' => 'Marcelo Quispe Mamani', 'email' => 'marcelo.quispe@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['147']],
            ['ci' => '3356382', 'nombre_completo' => 'Edgar Valentin Cazas Rojas', 'email' => 'edgar.cazas@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['151']],
            ['ci' => '4984421', 'nombre_completo' => 'Rolando Challco Vino', 'email' => 'rolando.challco@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['166']],
            ['ci' => '4907881', 'nombre_completo' => 'Rudy Ivan Achacayo Llusco', 'email' => 'rudy.achacayo@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Mecánica Automotriz', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $mazId, 'puede_verificar' => false, 'items' => ['674']],

            // ---------------- DOCENTES SIF ----------------
            ['ci' => '4241277', 'nombre_completo' => 'Felix Maldonado Aguilar', 'email' => 'felix.maldonado@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Sistemas Informáticos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $sifId, 'puede_verificar' => false, 'items' => ['103']],
            ['ci' => '6018234', 'nombre_completo' => 'Juan Carlos Mamani Nina', 'email' => 'juan.mamani@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Sistemas Informáticos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $sifId, 'puede_verificar' => false, 'items' => ['104']],
            ['ci' => '2623086', 'nombre_completo' => 'Enrique Bernardo Canqui Carvajal', 'email' => 'enrique.canqui@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Sistemas Informáticos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $sifId, 'puede_verificar' => false, 'items' => ['111']],
            ['ci' => '6032467', 'nombre_completo' => 'Jhony Chavez Quispe', 'email' => 'jhony.chavez@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Sistemas Informáticos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $sifId, 'puede_verificar' => false, 'items' => ['115']],
            ['ci' => '4258442', 'nombre_completo' => 'Hernando Sanabria Yupanqui', 'email' => 'hernando.sanabria@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Sistemas Informáticos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $sifId, 'puede_verificar' => false, 'items' => ['146']],
            ['ci' => '4880437', 'nombre_completo' => 'Betty Sandra Quispe Cortez', 'email' => 'betty.quispe@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Sistemas Informáticos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $sifId, 'puede_verificar' => false, 'items' => ['149']],
            ['ci' => '3451328', 'nombre_completo' => 'Patricia Regina Flores Chuquimia', 'email' => 'patricia.flores@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente Custodio', 'unidad' => 'Laboratorio 3', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $sifId, 'puede_verificar' => false, 'items' => ['131']],

            // ---------------- DOCENTES INA ----------------
            ['ci' => '4943262', 'nombre_completo' => 'Julio Fabian Palma', 'email' => 'julio.fabian@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['106']],
            ['ci' => '2556407', 'nombre_completo' => 'Vicente Ramos Mamani', 'email' => 'vicente.ramos@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['122']],
            ['ci' => '6958071', 'nombre_completo' => 'Sheila Jhoana Rocabado Garcia', 'email' => 'sheila.rocabado@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['126']],
            ['ci' => '4745843', 'nombre_completo' => 'Gladys Vasquez Huanca', 'email' => 'gladys.vasquez@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['129']],
            ['ci' => '2610990', 'nombre_completo' => 'Jose Miguel Barrios Lopez', 'email' => 'jose.barrios@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['145']],
            ['ci' => '4251679', 'nombre_completo' => 'Lilian Bustos Huanca', 'email' => 'lilian.bustos@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['137']],
            ['ci' => '6947598', 'nombre_completo' => 'Carla Esperanza Jimenez Rivera', 'email' => 'carla.jimenez@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['160']],
            ['ci' => '3520825', 'nombre_completo' => 'Guisela Maribel Fernandez Yllanes', 'email' => 'guisela.fernandez@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['631']],
            ['ci' => '4251675', 'nombre_completo' => 'Rosario Rose Mary Huanca Baldivieso', 'email' => 'rosario.huanca@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Industria de Alimentos', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $inaId, 'puede_verificar' => false, 'items' => ['710']],

            // ---------------- DOCENTES GTR ----------------
            ['ci' => '9992579', 'nombre_completo' => 'Magaly Quispe Callisaya', 'email' => 'magaly.quispe@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['99']],
            ['ci' => '5970478', 'nombre_completo' => 'Jose Luis Paredes Martinez', 'email' => 'jose.paredes@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['110']],
            ['ci' => '9994661', 'nombre_completo' => 'Fernando Sirpa Mamani', 'email' => 'fernando.sirpa@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['127']],
            ['ci' => '6945305', 'nombre_completo' => 'Mary Cruz Rodriguez Chuquimia', 'email' => 'mary.rodriguez@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['136']],
            ['ci' => '8264124', 'nombre_completo' => 'Carlos Christian Aguilar Ramos', 'email' => 'carlos.aguilar@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['138']],
            ['ci' => '8318114', 'nombre_completo' => 'Guido Limachi Huarcacho', 'email' => 'guido.limachi@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['139']],
            ['ci' => '7023871', 'nombre_completo' => 'Isaac Limbert Poma Murga', 'email' => 'isaac.poma@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['152']],
            ['ci' => '9980596', 'nombre_completo' => 'Rosario Machaca Paucara', 'email' => 'rosario.machaca@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['163']],
            ['ci' => '4273503', 'nombre_completo' => 'Jose Raul Rivera Salinas', 'email' => 'jose.rivera@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['165']],
            ['ci' => '6832733', 'nombre_completo' => 'Lennis Mabel Bautista Chipana', 'email' => 'lennis.bautista@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['168']],
            ['ci' => '6953456', 'nombre_completo' => 'Carmen Rosa Tarquino Mamani', 'email' => 'carmen.tarquino@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Gastronomía', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $gtrId, 'puede_verificar' => false, 'items' => ['712']],

            // ---------------- DOCENTES ELC ----------------
            ['ci' => '3428234', 'nombre_completo' => 'Gaby Patricia Minaya Flores', 'email' => 'gaby.minaya@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Electrónica', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $elcId, 'puede_verificar' => false, 'items' => ['101']],
            ['ci' => '5958760', 'nombre_completo' => 'Alan Elvis Ochoa Perez', 'email' => 'alan.ochoa@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Electrónica', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $elcId, 'puede_verificar' => false, 'items' => ['130']],
            ['ci' => '3898361', 'nombre_completo' => 'Juan Carlos Moron Zabala', 'email' => 'juan.moron@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Electrónica', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $elcId, 'puede_verificar' => false, 'items' => ['144']],
            ['ci' => '4367554', 'nombre_completo' => 'Javier Jimmy Tarqui Valeriano', 'email' => 'javier.tarqui@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Electrónica', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $elcId, 'puede_verificar' => false, 'items' => ['157']],
            ['ci' => '3530492', 'nombre_completo' => 'Juan Carlos Miranda Sanchez', 'email' => 'juan.miranda@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Escuela de Electrónica', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $elcId, 'puede_verificar' => false, 'items' => ['164']],

            // ---------------- TRANSVERSALES / AGRO ----------------
            ['ci' => '4810184', 'nombre_completo' => 'Venancio Esteban Cora Quispe', 'email' => 'venancio.cora@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Áreas Transversales', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => null, 'puede_verificar' => false, 'items' => ['153']],
            ['ci' => '3425619', 'nombre_completo' => 'Eloy Tantani Coaquira', 'email' => 'eloy.tantani@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente Agropecuaria', 'unidad' => 'Sub Sede Corpa - Agropecuaria', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $agroId, 'puede_verificar' => false, 'items' => ['155']],
            ['ci' => '5972043', 'nombre_completo' => 'Felipa Zambrana Mamani', 'email' => 'felipa.zambrana@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente Agropecuaria', 'unidad' => 'Sub Sede Corpa - Agropecuaria', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $agroId, 'puede_verificar' => false, 'items' => ['156']],
            ['ci' => '3489128', 'nombre_completo' => 'Santiago Chambi Macuchapi', 'email' => 'santiago.chambi@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente Agropecuaria', 'unidad' => 'Sub Sede Corpa - Agropecuaria', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => $agroId, 'puede_verificar' => false, 'items' => ['161']],
            ['ci' => '6138095', 'nombre_completo' => 'Pamela Quispe Coria', 'email' => 'pamela.quispe@itpm.edu.bo', 'password' => Hash::make('docente123'), 'cargo' => 'Docente', 'unidad' => 'Áreas Transversales', 'rol' => 'DOCENTE_CUSTODIO', 'estado' => 'ACTIVO', 'id_carrera' => null, 'puede_verificar' => false, 'items' => ['711']],
        ];

        // ============================================================
        // 3. CREAR USUARIOS + GUARDAR ITEMS EN MEMORIA
        // ============================================================
        $itemsPorUsuario = [];

        foreach ($personal as $persona) {
            $items = $persona['items'] ?? [];
            unset($persona['items']);

            $fila = User::updateOrCreate(['ci' => $persona['ci']], $persona);
            $itemsPorUsuario[$fila->id_usuario] = $items;
        }

        // ============================================================
        // 4. AMBIENTES
        // ============================================================
        $ambientes = [
            ['codigo' => 'LAB-1', 'nombre' => 'Laboratorio 1',            'bloque' => 'A', 'piso' => 1, 'carrera' => 'SIF'],
            ['codigo' => 'LAB-2', 'nombre' => 'Laboratorio 2',            'bloque' => 'A', 'piso' => 1, 'carrera' => 'SIF'],
            ['codigo' => 'LAB-3', 'nombre' => 'Laboratorio 3',            'bloque' => 'A', 'piso' => 1, 'carrera' => 'SIF'],
            ['codigo' => 'RED-1', 'nombre' => 'Redes',                    'bloque' => 'B', 'piso' => 1, 'carrera' => 'SIF'],
            ['codigo' => 'REC-1', 'nombre' => 'Rectorado',                'bloque' => 'A', 'piso' => 2, 'carrera' => 'ADM'],
            ['codigo' => 'SEC-1', 'nombre' => 'Secretaría General',       'bloque' => 'A', 'piso' => 2, 'carrera' => 'ADM'],
            ['codigo' => 'ADM-1', 'nombre' => 'Dirección Administrativa', 'bloque' => 'A', 'piso' => 2, 'carrera' => 'ADM'],
            ['codigo' => 'ALM-1', 'nombre' => 'Almacén',                  'bloque' => 'C', 'piso' => 1, 'carrera' => 'ADM'],
            ['codigo' => 'BIB-1', 'nombre' => 'Biblioteca',               'bloque' => 'B', 'piso' => 1, 'carrera' => 'ADM'],
        ];

        foreach ($ambientes as $ambiente) {
            Ambiente::updateOrCreate(
                ['codigo' => $ambiente['codigo']],
                [
                    'nombre' => $ambiente['nombre'],
                    'bloque' => $ambiente['bloque'],
                    'piso' => $ambiente['piso'],
                    'id_carrera' => $carreraIds[$ambiente['carrera']],
                ]
            );
        }

        $ambienteIds = [];
        foreach (Ambiente::all() as $ambiente) {
            $ambienteIds[$ambiente->codigo] = $ambiente->id_ambiente;
        }

        // ============================================================
        // 5. CATEGORÍAS
        // ============================================================
        $categorias = [
            ['codigo' => 'EQP-INF',  'nombre' => 'Equipos Informáticos',  'descripcion' => 'Equipos de cómputo y accesorios'],
            ['codigo' => 'EQP-ELC',  'nombre' => 'Equipos Electrónicos',  'descripcion' => 'Equipos electrónicos y componentes'],
            ['codigo' => 'MOB-URB',  'nombre' => 'Mobiliario',            'descripcion' => 'Mobiliario y enseres'],
            ['codigo' => 'MAT-CONS', 'nombre' => 'Material de Consumo',   'descripcion' => 'Escobas, papeleras, engrampadoras'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(['codigo' => $categoria['codigo']], $categoria);
        }

        // ============================================================
        // 6. FUENTES DE FINANCIAMIENTO
        // ============================================================
        $fuentes = [
            ['codigo' => 'GD',  'nombre' => 'Gobierno Departamental'],
            ['codigo' => 'GM',  'nombre' => 'Gobierno Municipal'],
            ['codigo' => 'GN',  'nombre' => 'Gobierno Nacional'],
            ['codigo' => 'PSP', 'nombre' => 'Proyecto Sociocomunitario Productivo'],
            ['codigo' => 'ADQ', 'nombre' => 'Adquisición'],
            ['codigo' => 'RPC', 'nombre' => 'Recursos Propios por Carrera'],
            ['codigo' => 'RPI', 'nombre' => 'Recursos Propios Institucionales'],
            ['codigo' => 'OTR', 'nombre' => 'Otros'],
        ];

        foreach ($fuentes as $fuente) {
            FuenteFinanciamiento::updateOrCreate(['codigo' => $fuente['codigo']], $fuente);
        }

        // ============================================================
        // 7. ITEMS (generados desde $personal)
        // ============================================================
        $itemIds = [];

        foreach ($personal as $persona) {
            $numerosItems = $persona['items'] ?? [];
            $unidad = $persona['unidad'] ?? null;
            $cargo = $persona['cargo'] ?? null;
            $idCarrera = $persona['id_carrera'] ?? null;

            $ambienteCodigo = null;
            if ($unidad) {
                if (str_contains($unidad, 'Laboratorio 1')) {
                    $ambienteCodigo = 'LAB-1';
                } elseif (str_contains($unidad, 'Laboratorio 2')) {
                    $ambienteCodigo = 'LAB-2';
                } elseif (str_contains($unidad, 'Laboratorio 3')) {
                    $ambienteCodigo = 'LAB-3';
                } elseif (str_contains($unidad, 'Redes')) {
                    $ambienteCodigo = 'RED-1';
                } elseif (str_contains($unidad, 'Rectorado')) {
                    $ambienteCodigo = 'REC-1';
                } elseif (str_contains($unidad, 'Secretaría')) {
                    $ambienteCodigo = 'SEC-1';
                } elseif (str_contains($unidad, 'Almacén')) {
                    $ambienteCodigo = 'ALM-1';
                } elseif (str_contains($unidad, 'Biblioteca')) {
                    $ambienteCodigo = 'BIB-1';
                } elseif (str_contains($unidad, 'Dirección Administrativa')) {
                    $ambienteCodigo = 'ADM-1';
                }
            }

            foreach ($numerosItems as $numeroItem) {
                $fila = Item::updateOrCreate(
                    ['numero_item' => $numeroItem],
                    [
                        'descripcion' => $cargo,
                        'unidad' => $unidad,
                        'id_carrera' => $idCarrera,
                        'id_ambiente' => $ambienteCodigo ? ($ambienteIds[$ambienteCodigo] ?? null) : null,
                        'estado' => 'VACANTE',
                    ]
                );

                $itemIds[$numeroItem] = $fila->id_item;
            }
        }

        // ============================================================
        // 8. ASIGNAR ITEMS A USUARIOS COMO CUSTODIOS TITULARES
        // ============================================================
        foreach ($itemsPorUsuario as $idUsuario => $numeros) {
            foreach ($numeros as $numero) {
                if (isset($itemIds[$numero])) {
                    Item::find($itemIds[$numero])->custodios()->syncWithoutDetaching([
                        $idUsuario => [
                            'tipo' => 'TITULAR',
                            'fecha_inicio' => '2026-01-01',
                            'fecha_fin' => null,
                        ],
                    ]);
                }
            }
        }

        // ============================================================
        // 9. RECALCULAR ESTADO DE CADA ITEM
        // ============================================================
        foreach (Item::all() as $fila) {
            $fila->recalcularEstado();
        }

        $this->command->info('✅ Base de datos poblada correctamente.');
        $this->command->info('   Carreras: '.count($carreraIds));
        $this->command->info('   Usuarios: '.count($personal));
        $this->command->info('   Items: '.count($itemIds));
        $this->command->info('   Ambientes: '.count($ambienteIds));
    }
}
