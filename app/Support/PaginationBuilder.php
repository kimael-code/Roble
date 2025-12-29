<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Centraliza la lógica de paginación para ViewModels.
 *
 * Esta clase elimina la duplicación de código de paginación
 * que estaba repetida en todas las clases Props.
 */
class PaginationBuilder
{
    /**
     * Número de items por página por defecto.
     */
    private int $defaultPerPage;

    public function __construct(int $defaultPerPage = 10)
    {
        $this->defaultPerPage = $defaultPerPage;
    }

    /**
     * Pagina una query de Eloquent o una relación.
     *
     * Lee los parámetros 'per_page' y 'page' del request automáticamente.
     *
     * @param Builder|Relation $query Query de Eloquent o relación a paginar
     * @param int|null $perPage Items por página (null = usar request o default)
     * @param int|null $page Página actual (null = usar request)
     * @param string|null $pageName Nombre del parámetro de página (null = 'page')
     * @param string|null $perPageName Nombre del parámetro per_page (null = 'per_page')
     * @return LengthAwarePaginator
     */
    public function paginate(
        Builder|Relation $query,
        ?int $perPage = null,
        ?int $page = null,
        ?string $pageName = null,
        ?string $perPageName = null
    ): LengthAwarePaginator {
        $perPageParam = $perPageName ?? 'per_page';
        $pageParam = $pageName ?? 'page';

        return $query->paginate(
            $perPage ?? request()->input($perPageParam, $this->defaultPerPage),
            ['*'],
            $pageParam,
            $page ?? request()->input($pageParam, 1)
        )->withQueryString();
    }
}

