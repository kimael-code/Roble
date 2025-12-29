<?php

namespace App\Models\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Observers\Security\PermissionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission as SpatiePermission;

#[ObservedBy([PermissionObserver::class])]
class Permission extends SpatiePermission
{
    /** @use HasFactory<\Database\Factories\Security\PermissionFactory> */
    use HasFactory;
    use LogsActivity;

    /**
     * Nombre usado para trazar el tipo de objeto.
     * @var string
     */
    protected $traceModelType = 'permiso';

    /**
     * Nombre usado para trazar el nombre del log.
     * @var string
     */
    protected $traceLogName = 'Seguridad/Permisos';

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
    protected $appends = ['created_at_human', 'updated_at_human',];

    protected function createdAtHuman(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes)
            {
                if ($attributes['created_at'] ?? null)
                {
                    return Carbon::createFromTimeString($attributes['created_at'])->isoFormat('L LT')
                        . ' ('
                        . Carbon::createFromTimeString($attributes['created_at'])->diffForHumans()
                        . ')';
                }
                else
                {
                    return null;
                }
            },
        );
    }

    protected function updatedAtHuman(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes)
            {
                if ($attributes['updated_at'] ?? null)
                {
                    return Carbon::createFromTimeString($attributes['updated_at'])->isoFormat('L LT')
                        . ' ('
                        . Carbon::createFromTimeString($attributes['updated_at'])->diffForHumans()
                        . ')';
                }
                else
                {
                    return null;
                }
            },
        );
    }

    public function getActivityLogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'description',
                'guard_name',
            ])
            ->logOnlyDirty()
            ->useLogName('Seguridad/Permisos')
            ->setDescriptionForEvent(fn(string $eventName) => __(':event :model [:modelName] [:modelDescription]', [
                'event' => __($eventName),
                'model' => __($this->traceModelType),
                'modelName' => $this->name,
                'modelDescription' => $this?->description,
            ]));
    }

    /**
     * Tap into the activity log to add request and causer metadata.
     * This ensures HTTP request data is captured in activity logs.
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        switch ($eventName)
        {
            case 'created':
                $activity->event = ActivityLog::EVENT_NAMES['created'];
                break;
            case 'updated':
                $activity->event = ActivityLog::EVENT_NAMES['updated'];
                break;
            case 'deleted':
                $activity->event = ActivityLog::EVENT_NAMES['deleted'];
                break;
            case 'restored':
                $activity->event = ActivityLog::EVENT_NAMES['restored'];
                break;
            default:
                break;
        }

        $activity->properties = $activity->properties
            ->put('causer', \App\Support\UserMetadata::capture())
            ->put('request', \App\Support\RequestMetadata::capture());
    }

    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when(empty($filters['sort_by'] ?? []), function (Builder $query)
            {
                $query->latest();
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $term)
            {
                $query->where(function (Builder $query) use ($term)
                {
                    $query->whereRaw('unaccent(name) ilike unaccent(?)', ["%$term%"])
                        ->orWhereRaw('unaccent(description) ilike unaccent(?)', ["%$term%"]);
                });
            })
            ->when($filters['sort_by'] ?? null, function (Builder $query, array $sorts)
            {
                foreach ($sorts as $field => $direction)
                {
                    switch ($field)
                    {
                        case 'created_at_human':
                            $query->orderBy('created_at', $direction);
                            break;
                        default:
                            $query->orderBy($field, $direction);
                            break;
                    }
                }
            })
            ->when($filters['roles'] ?? null, function (Builder $query, array $names)
            {
                $roles = Role::whereIn('name', $names)->get();

                $query->whereAttachedTo($roles);
            })
            ->when($filters['users'] ?? null, function (Builder $query, array $names)
            {
                foreach ($names as $username)
                {
                    $query
                        ->orWhereHas('users', function (Builder $query) use ($username)
                        {
                            $query->where('name', $username);
                        })
                        ->orWhereHas('roles', function (Builder $query) use ($username)
                        {
                            $user = User::where('name', $username)->first();

                            foreach ($user->roles as $role)
                            {
                                $query->where('id', $role->id);
                            }
                        });
                }
            });
    }
}
