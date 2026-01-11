<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Person;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    protected bool $notify = false;

    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(User $user, array $inputs): User
    {
        DB::transaction(function () use ($inputs, &$user)
        {
            $user->name = $inputs['name'];
            $user->email = $inputs['email'];
            $user->is_external = $inputs['is_external'];

            $user->save();

            if ($user->wasChanged())
            {
                $this->notify = true;

                $this->logger->logUpdated(
                    ActivityLog::LOG_NAMES['users'],
                    $user,
                    'actualizó usuario [:subject.name] [:subject.email]'
                );
            }

            $this->removeRoles($user, $inputs['roles']);
            $this->assignRoles($user, $inputs['roles']);
            $this->revokePermissions($user, $inputs['permissions']);
            $this->givePermissions($user, $inputs['permissions']);
            $this->setPerson($user, $inputs);

            if ($this->notify)
            {
                session()->flash('message', [
                    'content' => "{$user->name}",
                    'title' => '¡GUARDADO!',
                    'type' => 'success',
                ]);

                $users = User::permission('update users')->get()->filter(
                    fn(User $user) => $user->id != auth()->user()->id
                )->all();

                foreach ($users as $user)
                {
                    $user->notify(new ActionHandledOnModel(
                        auth()->user(),
                        [
                            'id' => $user->id,
                            'type' => 'usuario',
                            'name' => "({$user->name})",
                            'timestamp' => $user->updated_at,
                        ],
                        'updated',
                        ['routeName' => 'users', 'routeParam' => 'user']
                    ));
                }
            }
        });

        return $user;
    }

    private function removeRoles(User $user, array $roleNames): void
    {
        foreach ($user->roles as $role)
        {
            if (!in_array($role->name, $roleNames, true))
            {
                $user->removeRole($role->id);

                $this->logger->logAuthorized(
                    ActivityLog::LOG_NAMES['users'],
                    $user,
                    "desasignó rol [{$role->name}] a usuario [:subject.name]",
                    ['rol_desasignado' => $role, 'al_usuario' => $user]
                );

                $this->notify = true;
            }
        }
    }

    private function assignRoles(User $user, array $roleNames): void
    {
        foreach ($roleNames as $roleName)
        {
            $role = Role::findByName($roleName);

            if (!$user->hasRole($role->name))
            {
                $user->assignRole($role);

                $this->logger->logAuthorized(
                    ActivityLog::LOG_NAMES['users'],
                    $user,
                    "asignó rol [{$role->name}] a usuario [:subject.name]",
                    ['rol_asignado' => $role, 'al_usuario' => $user]
                );

                $this->notify = true;
            }
        }
    }

    public function revokePermissions(User $user, array $permissionDescriptions): void
    {
        foreach ($user->permissions as $permission)
        {
            if (!in_array($permission->description, $permissionDescriptions, true))
            {
                $user->revokePermissionTo($permission);

                $this->logger->logAuthorized(
                    ActivityLog::LOG_NAMES['users'],
                    $user,
                    "revocó permiso [{$permission->description}] a usuario [:subject.name]",
                    ['permiso_revocado' => $permission, 'al_usuario' => $user]
                );

                $this->notify = true;
            }
        }
    }

    private function givePermissions(User $user, array $permissionDescriptions): void
    {
        foreach ($permissionDescriptions as $permissionDescription)
        {
            $permission = Permission::where('description', $permissionDescription)->first();

            if (!$user->hasPermissionTo($permission->id))
            {
                $user->givePermissionTo($permission);

                $this->logger->logAuthorized(
                    ActivityLog::LOG_NAMES['users'],
                    $user,
                    "otorgó permiso [{$permission->description}] a usuario [:subject.name]",
                    ['permiso_otorgado' => $permission, 'al_usuario' => $user]
                );

                $this->notify = true;
            }
        }
    }

    private function setPerson(User $user, array $inputs): void
    {
        if ($inputs['is_external'])
        {
            // el usuario pasó de ser interno (corporativo) a externo (persona ajena a la organización)
            // por lo tanto se eliminan las asociaciones activas entre el usuario y las
            // unidades administrativas de la organización.
            foreach ($user->organizationalUnits as $ou)
            {
                $user->organizationalUnits()->detach($ou->id);

                $this->logger->logDeleted(
                    ActivityLog::LOG_NAMES['users'],
                    $user,
                    "desasoció usuario [:subject.name] de unidad administrativa [{$ou->name}]",
                    [
                        'usuario_desasociado' => $user,
                        'de_la_unidad_administrativa' => $ou,
                    ]
                );

                $this->notify = true;
            }
        }
        else
        {
            // el usuario sigue siendo corporativo pero es movido a otra unidad
            // administrativa (ua) dentro de la organización, se deben desactivar las
            // uas activas.
            foreach ($user->activeOrganizationalUnits as $ou)
            {
                if (!in_array($ou->name, $inputs['ou_names'], true))
                {
                    $user->activeOrganizationalUnits()->updateExistingPivot($ou->id, [
                        'disabled_at' => now(),
                    ]);

                    $this->logger->logDisabled(
                        ActivityLog::LOG_NAMES['users'],
                        $user,
                        "desactivó usuario [:subject.name] en unidad administrativa [{$ou->name}]",
                        [
                            'usuario_desactivado' => $user,
                            'en_la_unidad_administrativa' => $ou,
                        ]
                    );

                    $this->notify = true;
                }
            }
            // y ahora se deben registrar las nuevas asociaciones del usuario
            // a las nuevas unidades administrativas o, si ya existían, reactivarlas.
            foreach ($inputs['ou_names'] as $ouName)
            {
                $ou = OrganizationalUnit::where('name', $ouName)->first();

                if (!in_array($ouName, $user->organizationalUnits->pluck('name')->all(), true))
                {
                    $user->organizationalUnits()->attach($ou->id);

                    $this->logger->logCreated(
                        ActivityLog::LOG_NAMES['users'],
                        $user,
                        "asoció usuario [:subject.name] con unidad administrativa [{$ou->name}]",
                        [
                            'usuario_asociado' => $user,
                            'con_la_unidad_administrativa' => $ou,
                        ]
                    );

                    $this->notify = true;
                }
                else
                {
                    $user->organizationalUnits()->updateExistingPivot($ou->id, ['disabled_at' => null]);

                    $this->logger->logEnabled(
                        ActivityLog::LOG_NAMES['users'],
                        $user,
                        "activó usuario [:subject.name] en unidad administrativa [{$ou->name}]",
                        [
                            'usuario_activado' => $user,
                            'en_la_unidad_administrativa' => $ou,
                        ]
                    );

                    $this->notify = true;
                }
            }
        }

        if (array_key_exists('id_card', $inputs) && empty($inputs['id_card']))
        {
            $user->person()->delete();
            $this->notify = true;
        }
        elseif (isset($inputs["id_card"]) && isset($inputs["names"]) && isset($inputs["surnames"]))
        {
            $person = $user->person ?? new Person();
            $person->id_card = $inputs['id_card'];
            $person->names = $inputs['names'];
            $person->surnames = $inputs['surnames'];
            $person->position = $inputs['position'] ?? null;
            $person->staff_type = $inputs['staff_type'] ?? null;

            $user->person ?: $person->user()->associate($user);

            if ($person->isDirty())
            {
                $this->notify = true;
            }

            $person->save();
        }
    }
}
