<?php

namespace App\Actions\SuInstaller;

use App\Contracts\EmployeeRepository;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateNewSuperuser
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {
    }

    public function handle(array $data): User
    {
        $user = new User();
        $employee = $this->employeeRepository->find($data['id_card']);

        DB::transaction(function () use (&$user, $data, $employee)
        {
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->remember_token = Str::random(64);
            $user->is_active = true;
            $user->email_verified_at = now();
            $user->save();

            $person = new Person([
                'id_card' => $employee->id_card,
                'names' => $employee->names,
                'surnames' => $employee->surnames,
                'phones' => ['ext' => $employee->phone_ext],
                'position' => $employee->position,
                'staff_type' => $employee->staff_type_name,
            ]);
            $ou = OrganizationalUnit::where('code', $employee->org_unit_code)->first();

            $user->person()->save($person);
            $user->assignRole('Superusuario');
            $user->organizationalUnits()->attach($ou);
        });

        return $user;
    }

    /**
     * Static method for backwards compatibility.
     * @deprecated Use instance method handle() instead with DI.
     */
    public static function run(array $data): User
    {
        return app(self::class)->handle($data);
    }
}
