<?php

namespace App\Models;

use App\Models\Monitoring\ActivityLog;
use App\Support\Traits\MergesAppends;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class BaseModel extends Model
{
    use LogsActivity;
    use MergesAppends;

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

    protected function deletedAtHuman(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes)
            {
                if ($attributes['deleted_at'] ?? null)
                {
                    return Carbon::createFromTimeString($attributes['deleted_at'])->isoFormat('L LT')
                        . ' ('
                        . Carbon::createFromTimeString($attributes['deleted_at'])->diffForHumans()
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
            ->useLogName(__($this->traceLogName))
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => __(':event :model [:modelName]', [
                'event' => __($eventName),
                'model' => __($this->traceModelType),
                'modelName' => $this?->name ?? $this?->title ?? '',
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
            ->put('causer', User::with('person')->find(auth()->user()->id)->toArray())
            ->put('request', [
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('user-agent'),
                'user_agent_lang' => request()->header('accept-language'),
                'referer' => request()->header('referer'),
                'http_method' => request()->method(),
                'request_url' => request()->fullUrl(),
            ]);
    }
}
