<?php

namespace App\Models\Monitoring;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    public const EVENT_NAMES = [
        'created' => 'creación',
        'updated' => 'modificación',
        'deleted' => 'eliminación',
        'restored' => 'restauración',
        'enabled' => 'activación',
        'disabled' => 'desactivación',
        'authorized' => 'autorización',
        'logged_in' => 'inicio de sesión',
        'logged_out' => 'cierre de sesión',
        'failed_login' => 'inicio de sesión fallido',
        'locked' => 'bloqueo',
        'verified' => 'verificación',
        'password' => 'cambio de contraseña',
    ];

    public const LOG_NAMES = [
        'auth' => 'Autenticación',
        'password' => 'Configuración/Contraseña',
        'profile' => 'Configuración/Perfil',
        'orgs' => 'Ente/Entes',
        'ous' => 'Ente/Unidades Administrativas',
        'logs' => 'Monitoreo/Depuración',
        'permissions' => 'Seguridad/Permisos',
        'roles' => 'Seguridad/Roles',
        'users' => 'Seguridad/Usuarios',
    ];

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['created_at_human', 'updated_at_human', 'causer_name', 'ip_address',];

    protected function createdAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => Carbon::createFromTimeString($attributes['created_at'])
                ->isoFormat('L LT')
            . ' (' . Carbon::createFromTimeString($attributes['created_at'])->diffForHumans() . ')',
        );
    }

    protected function updatedAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => Carbon::createFromTimeString($attributes['updated_at'])
                ->isoFormat('L LT')
            . ' (' . Carbon::createFromTimeString($attributes['updated_at'])->diffForHumans() . ')',
        );
    }

    protected function causerName(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes)
            {
                $causerName = '';

                if (isset($attributes['causer_id']))
                {
                    $causerName = $this->causer?->name;
                }

                return $causerName;
            }
        );
    }

    protected function ipAddress(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes)
            {
                $properties = json_decode($attributes['properties'], true);

                return $properties && isset($properties['request']['ip_address'])
                    ? $properties['request']['ip_address']
                    : '';
            },
        );
    }

    public function causer(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when(empty($filters['sort_by'] ?? []), function (Builder $query)
            {
                $query->latest();
            })
            ->when($filters['search'] ?? null, function (Builder $query, $term)
            {
                $query->where(function (Builder $query) use ($term)
                {
                    $query->whereRaw('unaccent(description) ilike unaccent(?)', ["%$term%"]);
                });
            })
            ->when($filters['sort_by'] ?? null, function (Builder $query, array $sorts)
            {
                foreach ($sorts as $field => $direction)
                {
                    switch ($field)
                    {
                        case 'causer_name':
                            $query->join('users', 'users.id', '=', 'activity_log.causer_id')
                                ->orderBy('users.name', $direction);
                            break;
                        case 'created_at_human':
                            $query->orderBy('activity_log.created_at', $direction);
                            break;
                        case 'ip_address':
                            if ($direction === 'asc' || $direction === 'desc')
                            {
                                $query->orderByRaw("(properties->'request'->>'ip_address')::inet {$direction}");
                            }
                            break;

                        default:
                            $query->orderBy($field, $direction);
                            break;
                    }
                }
            })
            ->when($filters['date'] ?? null, function (Builder $query, array $date)
            {
                $query->whereDate('created_at', "{$date['year']}-{$date['month']}-{$date['day']}");
            })
            ->when($filters['date_range'] ?? null, function (Builder $query, array $dateRange)
            {
                $start = "{$dateRange['start']['year']}-{$dateRange['start']['month']}-{$dateRange['start']['day']}";
                $end = "{$dateRange['end']['year']}-{$dateRange['end']['month']}-{$dateRange['end']['day']}";
                $query->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end);
            })
            ->when($filters['users'] ?? null, function (Builder $query, array $users)
            {
                foreach ($users as $user)
                {
                    $query->whereHasMorph(
                        'causer',
                        User::class,
                        function (Builder $query) use ($user)
                        {
                            $query->where('name', $user);
                        }
                    );
                }
            })
            ->when($filters['events'] ?? null, function (Builder $query, array $events)
            {
                $query->whereIn('event', $events);
            })
            ->when($filters['modules'] ?? null, function (Builder $query, array $modules)
            {
                $query->whereIn('log_name', $modules);
            })
            ->when($filters['time'] ?? null, function (Builder $query, string $time)
            {
                $query->whereTime('created_at', $time);
            })
            ->when($filters['time_from'] ?? null, function (Builder $query, string $timeFrom)
            {
                $query->whereTime('created_at', '>=', $timeFrom);
            })
            ->when($filters['time_until'] ?? null, function (Builder $query, string $timeUntil)
            {
                $query->whereTime('created_at', '<=', $timeUntil);
            })
            ->when($filters['ips'] ?? null, function (Builder $query, array $ipAddresses)
            {
                foreach ($ipAddresses as $ipAddress)
                {
                    $query->where('properties->request->ip_address', $ipAddress)
                        ->orWhere('properties->request->ip_address', $ipAddress);
                }
            });
    }
}
