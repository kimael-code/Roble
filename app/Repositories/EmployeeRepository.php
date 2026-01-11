<?php

namespace App\Repositories;

use App\Contracts\EmployeeRepository as EmployeeContract;
use App\Dto\EmployeeDto;
use Illuminate\Support\Facades\DB;

/**
 * Implementación de referencia de EmployeeRepository para conexión a base de datos externa.
 *
 * Esta clase demuestra cómo implementar el contrato EmployeeRepository para obtener
 * datos de empleados activos desde una base de datos PostgreSQL externa.
 *
 * NOTA: Esta implementación está diseñada para un esquema de base de datos específico
 * (tablas: nomina.personal, nomina.tipo_personal, empresa.organigrama). Si desea conectar
 * su propia base de datos de empleados, debe:
 *
 * 1. Modificar esta clase para adaptar las consultas SQL a su esquema de base de datos
 * 2. Registrar su implementación en App\Providers\AppServiceProvider:
 *    $this->app->bind(EmployeeContract::class, EmployeeRepository::class);
 *
 * Por defecto, Roble utiliza JsonEmployeeRepository que lee datos de un archivo JSON
 * ubicado en database/data/employees.json para propósitos de demostración y pruebas.
 *
 * @see \App\Contracts\EmployeeRepository
 * @see \App\Repositories\JsonEmployeeRepository
 */
class EmployeeRepository implements EmployeeContract
{
    /**
     * Tipos de personal permitidos (códigos del sistema heredado).
     */
    private const ALLOWED_STAFF_TYPES = [
        'empleado' => '0000001',
        'empleadoContratado' => '0000002',
        'empleadoSuplente' => '0000003',
        'obrero' => '0000004',
        'obreroContratado' => '0000005',
        'obreroSuplente' => '0000006',
        'comisServicio' => '0000011',
        'altoNivel' => '0000016',
    ];

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        $results = DB::connection('organization')->select(
            $this->buildBaseQuery(),
            self::ALLOWED_STAFF_TYPES
        );

        return array_map(fn($employee) => new EmployeeDto(
            company_code: $employee->company_code,
            nationality: $employee->nationality,
            id_card: $employee->id_card,
            rif: $employee->rif,
            names: $employee->names,
            surnames: $employee->surnames,
            staff_type_code: $employee->staff_type_code,
            org_unit_code: $employee->org_unit_code,
            position: $employee->position,
            email: $employee->email,
            phone_ext: $employee->phone_ext,
            staff_type_name: $employee->staff_type_name,
            org_unit_name: $employee->org_unit_name,
        ), $results);
    }

    /**
     * {@inheritDoc}
     */
    public function find(string $idCard): ?EmployeeDto
    {
        $results = DB::connection('organization')->select(
            $this->buildBaseQuery() . ' WHERE nomina.personal.cedula = :idCard',
            array_merge(self::ALLOWED_STAFF_TYPES, ['idCard' => $idCard])
        );

        if (empty($results))
        {
            return null;
        }

        $employee = $results[0];

        return new EmployeeDto(
            company_code: $employee->company_code,
            nationality: $employee->nationality,
            id_card: $employee->id_card,
            rif: $employee->rif,
            names: $employee->names,
            surnames: $employee->surnames,
            staff_type_code: $employee->staff_type_code,
            org_unit_code: $employee->org_unit_code,
            position: $employee->position,
            email: $employee->email,
            phone_ext: $employee->phone_ext,
            staff_type_name: $employee->staff_type_name,
            org_unit_name: $employee->org_unit_name,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findByPartialIdCard(string $partialIdCard): array
    {
        $results = DB::connection('organization')->select(
            $this->buildBaseQuery() . ' WHERE nomina.personal.cedula ILIKE :partialIdCard',
            array_merge(self::ALLOWED_STAFF_TYPES, ['partialIdCard' => "$partialIdCard%"])
        );

        return array_map(fn($employee) => new EmployeeDto(
            company_code: $employee->company_code,
            nationality: $employee->nationality,
            id_card: $employee->id_card,
            rif: $employee->rif,
            names: $employee->names,
            surnames: $employee->surnames,
            staff_type_code: $employee->staff_type_code,
            org_unit_code: $employee->org_unit_code,
            position: $employee->position,
            email: $employee->email,
            phone_ext: $employee->phone_ext,
            staff_type_name: $employee->staff_type_name,
            org_unit_name: $employee->org_unit_name,
        ), $results);
    }

    /**
     * Construye la consulta base para empleados.
     */
    private function buildBaseQuery(): string
    {
        return 'SELECT
                nomina.personal.empresa_id AS "company_code",
                nomina.personal.nacionalidad AS "nationality",
                nomina.personal.cedula AS "id_card",
                nomina.personal.rif AS "rif",
                nomina.personal.nombres AS "names",
                nomina.personal.apellidos AS "surnames",
                nomina.personal.tipo_personal_id AS "staff_type_code",
                nomina.personal.unidad_administrativa_id AS "org_unit_code",
                nomina.personal.cargo AS "position",
                nomina.personal.correo AS "email",
                nomina.personal.telefono AS "phone_ext",
                nomina.tipo_personal.denominacion AS "staff_type_name",
                empresa.organigrama.nombre_unidad AS "org_unit_name"
            FROM
                nomina.personal
            INNER JOIN nomina.tipo_personal ON
                nomina.personal.id = nomina.tipo_personal.id
                AND nomina.personal.id IN (
                    :empleado, :empleadoContratado, :empleadoSuplente,
                    :obrero, :obreroContratado, :obreroSuplente,
                    :comisServicio, :altoNivel
                )
            INNER JOIN empresa.organigrama ON
                empresa.organigrama.unidad_administrativa_id = nomina.personal.unidad_administrativa_id
        ';
    }
}