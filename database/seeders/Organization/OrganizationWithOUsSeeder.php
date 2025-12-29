<?php

namespace Database\Seeders\Organization;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationalUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrganizationWithOUsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()->environment('local') ? $this->localSeed() : $this->nonLocalSeed();
    }

    private function localSeed(): void
    {
        // ente activo con sus unidades administrativas
        $activeOrganization = Organization::factory()->create();

        $rootOU = OrganizationalUnit::factory()
            ->for($activeOrganization)
            ->create(['organizational_unit_id' => null]);

        $firstLvlOus = OrganizationalUnit::factory()
            ->count(3)
            ->for($activeOrganization)
            ->create(['organizational_unit_id' => $rootOU->id]);

        foreach ($firstLvlOus as $ou)
        {
            OrganizationalUnit::factory()
                ->count(3)
                ->for($activeOrganization)
                ->create(['organizational_unit_id' => $ou->id]);
        }

        // ente inactivo con sus unidades administrativas
        $inactiveOrganization = Organization::factory()->disabled()->create();

        $rootOU = OrganizationalUnit::factory()
            ->for($inactiveOrganization)
            ->create(['organizational_unit_id' => null]);

        $firstLvlOus = OrganizationalUnit::factory()
            ->count(3)
            ->for($inactiveOrganization)
            ->create(['organizational_unit_id' => $rootOU->id]);

        foreach ($firstLvlOus as $ou)
        {
            OrganizationalUnit::factory()
                ->count(3)
                ->for($inactiveOrganization)
                ->create(['organizational_unit_id' => $ou->id]);
        }
    }

    private function nonLocalSeed(): void
    {
        $organizationID = DB::table('organizations')->insertGetId([
            'rif' => 'J-00000000-0',
            'name' => 'EMPRESA EJEMPLO S.A.',
            'acronym' => 'EJEMPLO',
            'address' => 'Av. Principal, Edificio Corporativo, Piso 10, Caracas, Venezuela.',
            'created_at' => 'now()',
            'updated_at' => 'now()',

        ]);

        $query = "INSERT INTO
             organizational_units (
                 organization_id,
                 organizational_unit_id,
                 code,
                 name,
                 acronym,
                 floor,
                 created_at,
                 updated_at,
                 disabled_at
             )
             VALUES
             ($organizationID,NULL,'1000000000','DIRECCIÓN GENERAL','DG','10','now()','now()',NULL),
             ($organizationID,1,'1100000000','GERENCIA DE RECURSOS HUMANOS','GRH','5','now()','now()',NULL),
             ($organizationID,2,'1110000000','DEPARTAMENTO DE SELECCIÓN','DS','5','now()','now()',NULL),
             ($organizationID,2,'1120000000','DEPARTAMENTO DE NÓMINA','DN','5','now()','now()',NULL),
             ($organizationID,2,'1130000000','DEPARTAMENTO DE CAPACITACIÓN','DC','5','now()','now()',NULL),
             ($organizationID,1,'1200000000','GERENCIA DE ADMINISTRACIÓN','GA','4','now()','now()',NULL),
             ($organizationID,6,'1210000000','DEPARTAMENTO DE CONTABILIDAD','DCO','4','now()','now()',NULL),
             ($organizationID,6,'1220000000','DEPARTAMENTO DE TESORERÍA','DT','4','now()','now()',NULL),
             ($organizationID,6,'1230000000','DEPARTAMENTO DE COMPRAS','DCOM','4','now()','now()',NULL),
             ($organizationID,1,'1300000000','GERENCIA DE TECNOLOGÍA','GT','8','now()','now()',NULL),
             ($organizationID,10,'1310000000','DEPARTAMENTO DE DESARROLLO','DD','8','now()','now()',NULL),
             ($organizationID,10,'1320000000','DEPARTAMENTO DE INFRAESTRUCTURA','DI','8','now()','now()',NULL),
             ($organizationID,10,'1330000000','DEPARTAMENTO DE SOPORTE','DSO','8','now()','now()',NULL),
             ($organizationID,1,'1400000000','GERENCIA DE SERVICIOS GENERALES','GSG','PB','now()','now()',NULL),
             ($organizationID,14,'1410000000','DEPARTAMENTO DE MANTENIMIENTO','DM','PB','now()','now()',NULL),
             ($organizationID,14,'1420000000','DEPARTAMENTO DE SEGURIDAD','DSE','PB','now()','now()',NULL);
        ";
        DB::unprepared($query);
    }
}
