<?php

namespace App\Rules;

use App\Repositories\EmployeeRepository;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveEmployee implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $activeEmployee = new EmployeeRepository()->find($value);

        if ($activeEmployee)
        {
            session()->put('employee', $activeEmployee[0]);
        }
        else
        {
            $fail('El N° de CI no corresponde a un empleado activo.');
        }
    }
}
