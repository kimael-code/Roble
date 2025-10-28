<?php

namespace App\Models\Security;

use App\Models\Monitoring\ActivityLog;
use App\Observers\Security\RoleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

#[ObservedBy([RoleObserver::class])]
class Role extends SpatieRole
{
    /** @use HasFactory<\Database\Factories\Security\RoleFactory> */
    use HasFactory;
    use LogsActivity;

    /**
     * Nombre usado para trazar el tipo de objeto.
     * @var string
     */
    protected $traceModelType = 'role';

    /**
     * Nombre usado para trazar el nombre del log.
     * @var string
     */
    protected $traceLogName = 'Security/Roles';

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
    protected $appends = ['created_at_human', 'updated_at_human'];

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
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('Seguridad/Roles')
            ->setDescriptionForEvent(fn(string $eventName) => __(':event :model [:modelName] [:modelDescription]', [
                'event' => __($eventName),
                'model' => __($this->traceModelType),
                'modelName' => $this->name,
                'modelDescription' => $this?->description,
            ]));
    }

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
            ->put('causer', \App\Models\User::with('person')->find(auth()->user()->id)->toArray())
            ->put('request', [
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('user-agent'),
                'user_agent_lang' => request()->header('accept-language'),
                'referer' => request()->header('referer'),
                'http_method' => request()->method(),
                'request_url' => request()->fullUrl(),
            ]);
    }

    #[Scope]
    protected function superuser(Builder $query): void
    {
        $query->when(!auth()->user()->hasRole(__('Superuser')), function (Builder $query)
        {
            $query->where('id', '<>', 1);
        });
    }

    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when(empty($filters) ?? null, function (Builder $query)
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
                    if ($field === 'record_status')
                    {
                        $query->orderBy('deleted_at', $direction);
                    }
                    if ($field === 'created_at_human')
                    {
                        $query->orderBy('created_at', $direction);
                    }
                    else
                    {
                        $query->orderBy($field, $direction);
                    }
                }
            })
            ->when($filters['permissions'] ?? null, function (Builder $query, array $permissionDescriptions)
            {
                $permissions = Permission::whereIn('description', $permissionDescriptions)->get();

                $query->whereAttachedTo($permissions);
            });
    }
}
