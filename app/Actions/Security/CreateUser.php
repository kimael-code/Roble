<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Person;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use App\Notifications\UserActivationMail;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateUser
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(array $inputs): User
    {
        $user = new User();

        DB::transaction(function () use ($inputs, &$user)
        {
            $user->name = $inputs['name'];
            $user->email = $inputs['email'];
            $user->password = Str::random(64);
            $user->remember_token = Str::random(64);
            $user->is_external = $inputs['is_external'];
            $user->save();

            session()->flash('message', [
                'content' => "{$user->name} ({$user->email})",
                'title' => '¡GUARDADO!',
                'type' => 'success',
            ]);

            $this->logger->logCreated(
                ActivityLog::LOG_NAMES['users'],
                $user,
                "creó usuario [:subject.name] [:subject.email]"
            );

            $this->assignRoles($user, $inputs['roles']);
            $this->givePermissions($user, $inputs['permissions']);
            $this->setPerson($user, $inputs);
            $this->setOrganizationalUnits($user, $inputs);

            $user->notify(new UserActivationMail());
        });

        return $user;
    }

    private function assignRoles(User $user, array $roleNames): void
    {
        $user->assignRole($roleNames);

        foreach ($roleNames as $roleName)
        {
            $role = Role::findByName($roleName);

            $this->logger->logAuthorized(
                ActivityLog::LOG_NAMES['users'],
                $user,
                "asignó rol [{$role->name}] a usuario [:subject.name]",
                ['rol_asignado' => $role, 'al_usuario' => $user]
            );
        }
    }

    private function givePermissions(User $user, array $permissionDescriptions): void
    {
        foreach ($permissionDescriptions as $permissionDescription)
        {
            $permission = Permission::where('description', $permissionDescription)->first();
            $user->givePermissionTo($permission);

            $this->logger->logAuthorized(
                ActivityLog::LOG_NAMES['users'],
                $user,
                "otorgó permiso [:{$permission->description}] a usuario [:subject.name]",
                ['permiso_otorgado' => $permission, 'al_usuario' => $user]
            );
        }
    }

    private function setPerson(User $user, array $inputs): void
    {
        if ($inputs['id_card'] && $inputs['names'] && $inputs['surnames'])
        {
            $person = new Person();
            $person->id_card = $inputs['id_card'];
            $person->names = $inputs['names'];
            $person->surnames = $inputs['surnames'];
            $person->position = $inputs['position'];
            $person->staff_type = $inputs['staff_type'];

            $person->user()->associate($user);
            $person->save();
        }
    }

    private function setOrganizationalUnits(User $user, array $inputs): void
    {
        if ($inputs['ou_names'])
        {
            foreach ($inputs['ou_names'] as $ouName)
            {
                $ou = OrganizationalUnit::where(DB::raw('LOWER(name)'), '=', DB::raw("LOWER('" . $ouName . "')"))->first();

                if (!$ou)
                {
                    $ou = OrganizationalUnit::where('code', request('org_unit_code'))->first();
                }

                $user->organizationalUnits()->attach($ou);

                $this->logger->logAuthorized(
                    ActivityLog::LOG_NAMES['users'],
                    $user,
                    "asoció usuario [:subject.name] con unidad administrativa [{$ou->name}]",
                    [
                        'usuario_asociado' => $user,
                        'con_la_unidad_administrativa' => $ou,
                    ]
                );
            }
        }
    }
}
