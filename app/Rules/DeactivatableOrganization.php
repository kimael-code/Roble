<?php

namespace App\Rules;

use App\Models\Organization\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DeactivatableOrganization implements ValidationRule
{
    public function __construct(
        protected Organization $organization,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $otherActiveOrganizations = Organization::whereNull('disabled_at')
                                                ->where('id', '!=', $this->organization->id)
                                                ->get();

        if ($value === true && $otherActiveOrganizations->isEmpty())
        {
            $fail('Debe haber al menos una organización activa.');
        }
    }
}
