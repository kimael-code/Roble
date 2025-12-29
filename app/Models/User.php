<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Organization\OrganizationalUnit;
use App\Notifications\ResetPassword;
use App\Observers\Security\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;
    use HasRoles;
    use SoftDeletes;

    /**
     * Nombre usado para trazar el tipo de objeto.
     * @var string
     */
    protected $traceModelType = 'usuario';

    /**
     * Nombre usado para trazar el nombre del log.
     * @var string
     */
    protected $traceLogName = 'Seguridad/Usuarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    public function activeOrganizationalUnits(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationalUnit::class)
            ->withTimestamps()
            ->wherePivotNull('disabled_at');
    }

    public function organizationalUnits(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationalUnit::class)->withTimestamps();
    }

    public function getActivityLogOptions(): \Spatie\Activitylog\LogOptions
    {
        return parent::getActivityLogOptions()
            ->logOnly([
                'name',
                'email',
                'is_active',
                'disabled_at',
                'email_verified_at',
            ])
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('disabled_at');
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
                        ->orWhereRaw('unaccent(email) ilike unaccent(?)', ["%$term%"]);
                });
            })
            ->when($filters['sort_by'] ?? null, function (Builder $query, array $sorts)
            {
                foreach ($sorts as $field => $direction)
                {
                    if ($field === 'deleted_at_human')
                    {
                        $query->orderBy('deleted_at', $direction);
                    }
                    elseif ($field === 'disabled_at_human')
                    {
                        $query->orderBy('disabled_at', $direction);
                    }
                    elseif ($field === 'created_at_human')
                    {
                        $query->orderBy('created_at', $direction);
                    }
                    else
                    {
                        $query->orderBy($field, $direction);
                    }
                }
            })
            ->when($filters['permissions'] ?? null, function (Builder $query, array $names)
            {
                foreach ($names as $name)
                {
                    $query->permission($name);
                }
            })
            ->when($filters['roles'] ?? null, function (Builder $query, array $roles)
            {
                foreach ($roles as $role)
                {
                    $query->role($role);
                }
            })
            ->when($filters['statuses'] ?? null, function (Builder $query, array $statuses)
            {
                foreach ($statuses as $status)
                {
                    switch ($status)
                    {
                        case 'active':
                            $query->where('is_active', true)
                                ->whereNull('disabled_at')
                                ->whereNull('deleted_at');
                            break;
                        case 'inactive':
                            $query->where('is_active', false)
                                ->whereNull('disabled_at')
                                ->whereNull('deleted_at');
                            break;
                        case 'disabled':
                            $query->whereNotNull('disabled_at');
                            break;
                        case 'deleted':
                            $query->whereNotNull('deleted_at');
                            break;
                    }
                }
            });
    }

    /**
     * Envía al usuario el correo electrónico de restableciemiento de contraseña.
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }
}
