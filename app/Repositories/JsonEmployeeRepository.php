<?php

namespace App\Repositories;

use App\Contracts\EmployeeRepository as EmployeeContract;
use App\Dto\EmployeeDto;

/**
 * Implementación de EmployeeRepository que lee datos de un archivo JSON.
 *
 * Esta implementación es útil para:
 * - Pruebas y desarrollo local
 * - Demostraciones del sistema
 * - Entornos donde no se dispone de una base de datos externa de empleados
 *
 * Para implementar su propia versión conectada a una base de datos real,
 * consulte la clase EmployeeRepository como referencia de implementación.
 *
 * @see \App\Repositories\EmployeeRepository
 */
class JsonEmployeeRepository implements EmployeeContract
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $employees = [];

    public function __construct()
    {
        $path = database_path('data/employees.json');

        if (file_exists($path))
        {
            $content = file_get_contents($path);
            $this->employees = json_decode($content, true) ?? [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return array_map(
            fn(array $employee) => $this->mapToDto($employee),
            $this->employees
        );
    }

    /**
     * {@inheritDoc}
     */
    public function find(string $idCard): ?EmployeeDto
    {
        foreach ($this->employees as $employee)
        {
            if ($employee['id_card'] === $idCard)
            {
                return $this->mapToDto($employee);
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByPartialIdCard(string $partialIdCard): array
    {
        $results = array_filter(
            $this->employees,
            fn(array $employee) => str_starts_with($employee['id_card'], $partialIdCard)
        );

        return array_map(
            fn(array $employee) => $this->mapToDto($employee),
            $results
        );
    }

    /**
     * Convierte un array asociativo en un EmployeeDto.
     */
    private function mapToDto(array $employee): EmployeeDto
    {
        return new EmployeeDto(
            company_code: $employee['company_code'],
            nationality: $employee['nationality'],
            id_card: $employee['id_card'],
            rif: $employee['rif'] ?? null,
            names: $employee['names'],
            surnames: $employee['surnames'],
            staff_type_code: $employee['staff_type_code'],
            org_unit_code: $employee['org_unit_code'],
            position: $employee['position'],
            email: $employee['email'] ?? null,
            phone_ext: $employee['phone_ext'] ?? null,
            staff_type_name: $employee['staff_type_name'],
            org_unit_name: $employee['org_unit_name'],
        );
    }
}
