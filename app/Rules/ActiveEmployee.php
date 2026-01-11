<?php

namespace App\Rules;

use App\Contracts\EmployeeRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveEmployee implements ValidationRule
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $activeEmployee = $this->employeeRepository->find($value);

        if ($activeEmployee && $activeEmployee->email)
        {
            session()->put('employee', $activeEmployee);
        }
        elseif ($activeEmployee && !$activeEmployee->email)
        {
            $fail('Correo institucional no definido. Comuníquese con Recursos Humanos.');
        }
        else
        {
            $fail('El N° de CI no corresponde a un empleado activo.');
        }
    }
}
