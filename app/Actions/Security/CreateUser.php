<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\Organization\OrganizationalUnit;
use App\Models\Person;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUser
{
    public static function handle(array $inputs): User
    {
        $user = new User();

        DB::transaction(function () use ($inputs, &$user)
        {
            $user->name = $inputs['name'];
            $user->email = $inputs['email'];
            $user->password = $inputs['id_card'] ? Hash::make($inputs['id_card']) : Hash::make($inputs['name']);
            $user->remember_token = Str::random();
            $user->is_external = $inputs['is_external'];
            $user->save();

            activity(ActivityLog::LOG_NAMES['users'])
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->event(ActivityLog::EVENT_NAMES['created'])
                ->withProperties([
                    'attributes' => $user->toArray(),
                    'request' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->header('user-agent'),
                        'user_agent_lang' => request()->header('accept-language'),
                        'referer' => request()->header('referer'),
                        'http_method' => request()->method(),
                        'request_url' => request()->fullUrl(),
                    ],
                    'causer' => User::with('person')->find(auth()->user()->id)->toArray(),
                ])
                ->log(__(':event :model [:modelName] [:modelEmail]', [
                    'event' => __($event),
                    'model' => $user->traceModelType,
                    'modelName' => $user->name,
                    'modelEmail' => $user->email,
                ]));

            self::assignRoles($user, $inputs['roles']);
            self::givePermissions($user, $inputs['permissions']);
            self::setPerson($user, $inputs);
            self::setOrganizationalUnits($user, $inputs);
        });

        return $user;
    }

    private static function assignRoles(User $user, array $roleNames): void
    {
        $user->assignRole($roleNames);

        foreach ($roleNames as $roleName)
        {
            $role = Role::findByName($roleName);
            $authUser = auth()->user();

            activity(ActivityLog::LOG_NAMES['users'])
                ->causedBy($authUser)
                ->performedOn($user)
                ->event(ActivityLog::EVENT_NAMES['authorized'])
                ->withProperties([
                    __('assigned_role') => $role,
                    __('to_user') => $user,
                    'causer' => User::with('person')->find($authUser->id)->toArray(),
                    'request' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->header('user-agent'),
                        'user_agent_lang' => request()->header('accept-language'),
                        'referer' => request()->header('referer'),
                        'http_method' => request()->method(),
                        'request_url' => request()->fullUrl(),
                    ]
                ])
                ->log(__('assigned role [:rolename] to user [:user]', [
                    'rolename' => $role->name,
                    'user' => $user->name,
                ]));
        }
    }

    private static function givePermissions(User $user, array $permissionDescriptions): void
    {
        foreach ($permissionDescriptions as $permissionDescription)
        {
            $permission = Permission::where('description', $permissionDescription)->first();
            $user->givePermissionTo($permission);
            $authUser = auth()->user();

            activity(ActivityLog::LOG_NAMES['users'])
                ->causedBy($authUser)
                ->performedOn($user)
                ->event(ActivityLog::EVENT_NAMES['authorized'])
                ->withProperties([
                    __('granted_permission') => $permission,
                    __('to_user') => $user,
                    'causer' => User::with('person')->find($authUser->id)->toArray(),
                    'request' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->header('user-agent'),
                        'user_agent_lang' => request()->header('accept-language'),
                        'referer' => request()->header('referer'),
                        'http_method' => request()->method(),
                        'request_url' => request()->fullUrl(),
                    ]
                ])
                ->log(__('granted permission [:permission] to user [:user]', [
                    'permission' => $permission->description,
                    'user' => $user->name,
                ]));
        }
    }

    private static function setPerson(User $user, array $inputs): void
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

    private static function setOrganizationalUnits(User $user, array $inputs): void
    {
        if ($inputs['ou_names'])
        {
            foreach ($inputs['ou_names'] as $ouName)
            {
                $ou = OrganizationalUnit::where(DB::raw('LOWER(name)'), '=', DB::raw("LOWER('" . $ouName . "')"))->first();

                $user->organizationalUnits()->attach($ou);

                $authUser = auth()->user();

                activity(ActivityLog::LOG_NAMES['users'])
                    ->causedBy($authUser)
                    ->performedOn($user)
                    ->event(ActivityLog::EVENT_NAMES['authorized'])
                    ->withProperties([
                        __('associated_user') => $user,
                        __('with_administrative_unit') => $ou,
                        'causer' => User::with('person')->find($authUser->id)->toArray(),
                        'request' => [
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->header('user-agent'),
                            'user_agent_lang' => request()->header('accept-language'),
                            'referer' => request()->header('referer'),
                            'http_method' => request()->method(),
                            'request_url' => request()->fullUrl(),
                        ]
                    ])
                    ->log(__('associated user [:user] with administrative unit [:ou]', [
                        'user' => $user->name,
                        'ou' => $ou->name,
                    ]));
            }
        }
    }
}
