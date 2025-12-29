<?php

namespace App\Models\Organization;

use App\Models\BaseModel;
use App\Observers\Organization\OrganizationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[ObservedBy([OrganizationObserver::class])]
class Organization extends BaseModel
{
    /** @use HasFactory<\Database\Factories\Organization\OrganizationFactory> */
    use HasFactory;

    /**
     * Nombre usado para trazar el tipo de objeto.
     * @var string
     */
    protected $traceModelType = 'ente';

    /**
     * Nombre usado para trazar el nombre del log.
     * @var string
     */
    protected $traceLogName = 'Ente/Entes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rif',
        'name',
        'logo_path',
        'acronym',
        'address',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'disabled_at' => 'datetime',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['logo_url', 'status'];

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => isset($attributes['logo_path'])
            ? Storage::url($attributes['logo_path'])
            : ''
        );
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => isset($attributes['disabled_at']) ? 'INACTIVO' : 'ACTIVO'
        );
    }

    public function getActivityLogOptions(): \Spatie\Activitylog\LogOptions
    {
        return parent::getActivityLogOptions()
            ->logOnly([
                'name',
                'acronym',
                'rif',
                'address',
                'logo_path',
                'disabled_at',
            ]);
    }

    public function organizationalUnits(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class);
    }

    public function activeOrganizationalUnits(): HasMany
    {
        return $this->hasMany(OrganizationalUnit::class)->whereNull('disabled_at');
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
                $query->orderByDesc('disabled_at')->latest();
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $term)
            {
                $query->where(function (Builder $query) use ($term)
                {
                    $query->whereRaw('unaccent(name) ilike unaccent(?)', ["%$term%"])
                        ->orWhereRaw('unaccent(rif) ilike unaccent(?)', ["%$term%"])
                        ->orWhereRaw('unaccent(acronym) ilike unaccent(?)', ["%$term%"]);
                });
            })
            ->when($filters['sort_by'] ?? null, function (Builder $query, array $sorts)
            {
                foreach ($sorts as $field => $direction)
                {
                    if ($field === 'status')
                    {
                        $newDirection = $direction === 'asc' ? 'desc' : 'asc';
                        $query->orderBy('disabled_at', $newDirection);
                    }
                    else
                    {
                        $query->orderBy($field, $direction);
                    }
                }
            });
    }
}
