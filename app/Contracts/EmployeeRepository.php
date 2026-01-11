<?php

namespace App\Contracts;

use App\Dto\EmployeeDto;

interface EmployeeRepository
{
    /**
     * Devuelve todos los empleados del ente u organización.
     * @return \App\Dto\EmployeeDto[]
     */
    public function all(): array;

    /**
     * Devuelve un empleado del ente u organización dado su número de cédula **exacto**.
     * @param string $idCard Número de cédula completo del empleado a buscar.
     * @return \App\Dto\EmployeeDto|null
     */
    public function find(string $idCard): ?EmployeeDto;

    /**
     * Devuelve todos los empleados cuyo número de cédula comience con el prefijo dado.
     * @param string $partialIdCard Prefijo del número de cédula (ej. '12292').
     * @return \App\Dto\EmployeeDto[]
     */
    public function findByPartialIdCard(string $partialIdCard): array;
}