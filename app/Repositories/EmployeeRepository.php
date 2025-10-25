<?php

namespace App\Repositories;

use App\Contracts\EmployeeRepository as EmployeeContract;
use Illuminate\Support\Collection;

class EmployeeRepository implements EmployeeContract
{
    /**
     * @return Collection<int, object>
     */
    private static function getData(): Collection
    {
        return collect([
            (object) [
                'company_code' => '001',
                'nationality' => 'V',
                'id_card' => '12345678',
                'rif' => 'V123456789',
                'names' => 'John',
                'surnames' => 'Doe',
                'staff_type_code' => '0000001',
                'org_unit_code' => 'OU001',
                'position' => 'Developer',
                'email' => 'john.doe@example.com',
                'phone_ext' => '123',
                'staff_type_name' => 'Empleado',
                'org_unit_name' => 'IT Department',
            ],
            (object) [
                'company_code' => '001',
                'nationality' => 'E',
                'id_card' => '87654321',
                'rif' => 'E876543219',
                'names' => 'Jane',
                'surnames' => 'Smith',
                'staff_type_code' => '0000002',
                'org_unit_code' => 'OU002',
                'position' => 'Designer',
                'email' => 'jane.smith@example.com',
                'phone_ext' => '456',
                'staff_type_name' => 'Empleado Contratado',
                'org_unit_name' => 'Design Department',
            ],
            (object) [
                'company_code' => '001',
                'nationality' => 'V',
                'id_card' => '11223344',
                'rif' => 'V112233445',
                'names' => 'Peter',
                'surnames' => 'Jones',
                'staff_type_code' => '0000004',
                'org_unit_code' => 'OU003',
                'position' => 'Project Manager',
                'email' => 'peter.jones@example.com',
                'phone_ext' => '789',
                'staff_type_name' => 'Obrero',
                'org_unit_name' => 'Management',
            ],
        ]);
    }

    public function all(): array
    {
        return self::getData()->all();
    }

    public function find($idCard): array
    {
        if (empty($idCard))
        {
            return [];
        }

        return self::getData()->filter(function ($employee) use ($idCard)
        {
            return str_contains(mb_strtolower($employee->id_card), mb_strtolower($idCard));
        })->values()->all();
    }
}
