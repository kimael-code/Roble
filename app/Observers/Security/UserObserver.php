<?php

namespace App\Observers\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Notifications\ActionHandledOnModel;
use App\Support\RequestMetadata;
use App\Support\UserMetadata;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Determinar si fue auto-registro o creación por admin
        $wasCreatedByAdmin = auth()->check() && auth()->id() !== $user->id;

        if ($wasCreatedByAdmin)
        {
            // Notificar a usuarios relevantes:
            // 1. Usuarios con permiso 'create new users'
            // 2. Superusuarios (tienen acceso completo al sistema)
            // Excluir: creador, usuario creado, y usuarios inactivos
            $usersToNotify = User::where(function ($query)
            {
                $query->permission('create new users')
                    ->orWhereHas('roles', function ($q)
                    {
                        $q->where('name', 'Superusuario');
                    });
            })
                ->where('id', '!=', auth()->id())      // Excluir al creador
                ->where('id', '!=', $user->id)         // Excluir al usuario creado
                ->whereNull('disabled_at')             // Solo usuarios activos
                ->get();

            foreach ($usersToNotify as $userToNotify)
            {
                $userToNotify->notify(new ActionHandledOnModel(
                    auth()->user(),
                    [
                        'id' => $user->id,
                        'type' => 'usuario',
                        'name' => $user->name,
                        'timestamp' => $user->created_at,
                    ],
                    'created',
                    ['routeName' => 'users', 'routeParam' => 'user']
                ));
            }
        }

        // Emitir evento para actualizar estadísticas del dashboard
        $calculator = new \App\Support\Dashboard\DashboardStatsCalculator();
        event(new \App\Events\DashboardStatsUpdated([
            'users' => $calculator->getUsersStats(),
            'roles' => $calculator->getRolesStats(),
        ]));
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Verificación defensiva: solo enviar notificaciones si hay un usuario autenticado
        // Esto previene excepciones en casos edge donde se actualiza un usuario sin autenticación
        if (!auth()->check())
        {
            return;
        }

        // Ignorar actualizaciones que solo involucran remember_token
        // Esto ocurre típicamente durante el cierre de sesión y no debe generar notificaciones
        $changedAttributes = array_keys($user->getChanges());
        $ignoredAttributes = ['remember_token', 'updated_at'];
        $relevantChanges = array_diff($changedAttributes, $ignoredAttributes);

        if (empty($relevantChanges))
        {
            return;
        }

        // Notificar a usuarios relevantes:
        // 1. Usuarios con permiso 'update users'
        // 2. Superusuarios (tienen acceso completo al sistema)
        // Excluir: usuario que realizó la acción y usuarios inactivos
        $usersToNotify = User::where(function ($query)
        {
            $query->permission('update users')
                ->orWhereHas('roles', function ($q)
                {
                    $q->where('name', 'Superusuario');
                });
        })
            ->where('id', '!=', auth()->id())      // Excluir al que realizó la actualización
            ->whereNull('disabled_at')             // Solo usuarios activos
            ->get();

        foreach ($usersToNotify as $userToNotify)
        {
            $userToNotify->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'id' => $user->id,
                    'type' => 'usuario',
                    'name' => $user->name,
                    'timestamp' => $user->updated_at,
                ],
                'updated',
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }

        // Emitir evento para actualizar estadísticas del dashboard
        $calculator = new \App\Support\Dashboard\DashboardStatsCalculator();
        event(new \App\Events\DashboardStatsUpdated([
            'users' => $calculator->getUsersStats(),
            'roles' => $calculator->getRolesStats(),
        ]));
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        activity(ActivityLog::LOG_NAMES['users'])
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event(ActivityLog::EVENT_NAMES['deleted'])
            ->withProperties([
                'old' => $user->toArray(),
                'request' => RequestMetadata::capture(),
                'causer' => UserMetadata::capture(),
            ])
            ->log('eliminó usuario [:subject.name] [:subject.email]');

        session()->flash('message', [
            'content' => "{$user->name}",
            'title' => '¡ELIMINADO!',
            'type' => 'danger',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('delete users')
                ->orWhereHas('roles', function ($q)
                {
                    $q->where('name', 'Superusuario');
                });
        })
            ->where('id', '!=', auth()->id())
            ->whereNull('disabled_at')
            ->get();

        foreach ($usersToNotify as $userToNotify)
        {
            $userToNotify->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'type' => 'usuario',
                    'name' => "{$user->name}",
                    'timestamp' => now(),
                ],
                'deleted',
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        activity(ActivityLog::LOG_NAMES['users'])
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event(ActivityLog::EVENT_NAMES['restored'])
            ->withProperties([
                'attributes' => $user->toArray(),
                'request' => RequestMetadata::capture(),
                'causer' => UserMetadata::capture(),
            ])
            ->log('restauró usuario [:subject.name] [:subject.email]');

        session()->flash('message', [
            'content' => "{$user->name}",
            'title' => '¡RESTAURADO!',
            'type' => 'success',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('restore users')
                ->orWhereHas('roles', function ($q)
                {
                    $q->where('name', 'Superusuario');
                });
        })
            ->where('id', '!=', auth()->id())
            ->whereNull('disabled_at')
            ->get();

        foreach ($usersToNotify as $userToNotify)
        {
            $userToNotify->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'id' => $user->id,
                    'type' => 'usuario',
                    'name' => "{$user->name}",
                    'timestamp' => $user->updated_at,
                ],
                'restored',
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        activity(ActivityLog::LOG_NAMES['users'])
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event(ActivityLog::EVENT_NAMES['deleted'])
            ->withProperties([
                'old' => $user->toArray(),
                'request' => RequestMetadata::capture(),
                'causer' => UserMetadata::capture(),
            ])
            ->log('eliminó permanentemente el usuario [:subject.name] [:subject.email]');

        session()->flash('message', [
            'content' => "{$user->name}",
            'title' => '¡ELIMINADO PERMANENTEMENTE!',
            'type' => 'danger',
        ]);

        $usersToNotify = User::where(function ($query)
        {
            $query->permission('delete users')
                ->orWhereHas('roles', function ($q)
                {
                    $q->where('name', 'Superusuario');
                });
        })
            ->where('id', '!=', auth()->id())
            ->whereNull('disabled_at')
            ->get();

        foreach ($usersToNotify as $userToNotify)
        {
            $userToNotify->notify(new ActionHandledOnModel(
                auth()->user(),
                [
                    'type' => 'usuario',
                    'name' => "{$user->name}",
                    'timestamp' => now(),
                ],
                'f_deleted',
                ['routeName' => 'users', 'routeParam' => 'user']
            ));
        }

        // Emitir evento para actualizar estadísticas del dashboard
        $calculator = new \App\Support\Dashboard\DashboardStatsCalculator();
        event(new \App\Events\DashboardStatsUpdated([
            'users' => $calculator->getUsersStats(),
            'roles' => $calculator->getRolesStats(),
        ]));
    }
}
