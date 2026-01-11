<?php

namespace App\Actions\Fortify;

use App\Models\Organization\OrganizationalUnit;
use App\Models\Person;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Rules\ActiveEmployee;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'id_card' => [
                'required',
                'numeric',
                'integer',
                'max_digits:8',
                Rule::unique(Person::class),
                new ActiveEmployee($this->employeeRepository)
            ],
            'password' => $this->passwordRules(),
        ], attributes: [
            'id_card' => 'Número de CI',
            'password' => 'Contraseña',
            'password_confirm' => 'Confirmar contraseña'
        ])->validate();

        $employee = $this->employeeRepository->find($input['id_card']);

        $user = User::create([
            'name' => Str::before($employee->email, '@'),
            'email' => $employee->email,
            'password' => $input['password'],
            'remember_token' => Str::random(60),
            'is_active' => true,
        ]);
        $person = new Person([
            'id_card' => $employee->id_card,
            'names' => $employee->names,
            'surnames' => $employee->surnames,
            'phones' => ['ext' => $employee->phone_ext],
            'position' => $employee->position,
            'staff_type' => $employee->staff_type_name,
        ]);
        $person->user()->associate($user);
        $person->save();

        $ou = OrganizationalUnit::where('code', $employee->org_unit_code)->first();
        $user->organizationalUnits()->attach($ou);

        return $user;
    }
}
