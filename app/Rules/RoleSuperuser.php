<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RoleSuperuser implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array('Superusuario', $value) && !auth()->user()->hasRole('Superusuario'))
        {
            $fail('El rol Superusuario solo puede ser asignado por otro superusuario.');
        }
    }
}
