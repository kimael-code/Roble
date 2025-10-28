<?php

namespace App\Actions\Security;

use App\Models\Organization\Organization;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Support\DataExport\BasePdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ExportRolesToPdf extends BasePdf
{
    public function __construct(
        protected string $orientation = 'L',
        protected string $format = 'LETTER',
        private array $filters = [],
    ) {
        parent::__construct(
            orientation: $orientation,
            format: $format,
        );
    }

    public function Header(): void
    {
        parent::Header();

        $filters = $this->getFilters();

        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->setFillColor(0, 53, 41);
        $this->setTextColor(255, 255, 255);
        $this->Cell(w: 0, txt: '1. FILTROS APLICADOS', border: 0, ln: 1, fill: true);
        $this->setTextColor(0, 0, 0);
        $this->MultiCell(w: 40, h: 0, align: 'L', ln: 0, txt: 'Buscar');
        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->MultiCell(w: 0, h: 0, align: 'L', ln: 1, txt: $filters['search'] ?: 'Todo');
        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->MultiCell(w: 40, h: 0, align: 'L', border: 'T', ln: 0, txt: 'Permiso(s)');
        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->MultiCell(w: 0, h: 0, align: 'L', border: 'T', ln: 1, txt: $filters['permissions'] ?: 'Todos');

        $this->setLineStyle([
            'width' => 0.75 / $this->k,
            'cap' => 'butt',
            'join' => 'mitter',
            'dash' => 0,
            'color' => [0, 0, 0],
        ]);
        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->setFillColor(0, 53, 41);
        $this->setTextColor(255, 255, 255);
        $this->Cell(w: 0, txt: '2. DETALLE DE LOS ROLES REGISTRADOS', border: 0, ln: 1, fill: true);
        $this->setTextColor(0, 0, 0);

        // establece el margen superior a la altura ocupada por el header
        $this->tMargin = $this->GetY();
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    }

    public function make(): string
    {
        // metadatos del archivo
        $this->setTitle('REPORTE: ROLES');
        $this->setSubject('Reporte de Roles registrados');
        $this->setKeywords('reporte, PDF, rol, roles');

        $organizationLogo = Organization::active()->first()->logo_path ?? '';

        $this->setHeaderData(
            ln: storage_path("app/public/{$organizationLogo}"),
            lw: 60,
            ht: 'REPORTE: ROLES',
            hs: now()->isoFormat('L LTS'),
            tc: [0, 30, 15],
            lc: [0, 128, 100],
        );
        $this->setFooterData(
            tc: [0, 30, 15],
            lc: [0, 128, 100],
        );

        $this->setFooterFont(['helvetica', '', 8]);

        $this->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $this->setMargins(PDF_MARGIN_LEFT, 77.5, PDF_MARGIN_RIGHT);
        $this->setHeaderMargin();
        $this->setFooterMargin(15);

        $this->setAutoPageBreak(true, 20);

        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------

        $roles = Role::filter($this->filters)->get();

        foreach ($roles as $key => $role)
        {
            $rolePermissionsCreate = $role->permissions
                ->filter(
                    fn(Permission $permission) => isset($this->filters['permissions'])
                    ? Str::contains($permission->description, $this->filters['permissions']) && Str::startsWith($permission->name, 'create')
                    : Str::startsWith($permission->name, 'create')
                )
                ->sortBy('description')
                ->values()
                ->all();
            $rolePermissionsRead = $role->permissions
                ->filter(
                    fn(Permission $permission) => isset($this->filters['permissions'])
                    ? Str::contains($permission->description, $this->filters['permissions']) && Str::startsWith($permission->name, 'read')
                    : Str::startsWith($permission->name, 'read')
                )
                ->sortBy('description')
                ->values()
                ->all();
            $rolePermissionsUpdate = $role->permissions
                ->filter(
                    fn(Permission $permission) => isset($this->filters['permissions'])
                    ? Str::contains($permission->description, $this->filters['permissions']) && Str::startsWith($permission->name, ['enable', 'disable', 'restore', 'update'])
                    : Str::startsWith($permission->name, ['enable', 'disable', 'restore', 'update'])
                )
                ->sortBy('description')
                ->values()
                ->all();
            $rolePermissionsDelete = $role->permissions
                ->filter(
                    fn(Permission $permission) => isset($this->filters['permissions'])
                    ? Str::contains($permission->description, $this->filters['permissions']) && Str::startsWith($permission->name, ['delete', 'force'])
                    : Str::startsWith($permission->name, ['delete', 'force'])
                )
                ->sortBy('description')
                ->values()
                ->all();
            $rolePermissionsExport = $role->permissions
                ->filter(
                    fn(Permission $permission) => isset($this->filters['permissions'])
                    ? Str::contains($permission->description, $this->filters['permissions']) && Str::startsWith($permission->name, 'export')
                    : Str::startsWith($permission->name, 'export')
                )
                ->sortBy('description')
                ->values()
                ->all();

            $html = View::make('pdf.roles.index', [
                'key' => $key,
                'role' => $role,
                'rolePermissionsCreate' => $rolePermissionsCreate,
                'rolePermissionsRead' => $rolePermissionsRead,
                'rolePermissionsUpdate' => $rolePermissionsUpdate,
                'rolePermissionsDelete' => $rolePermissionsDelete,
                'rolePermissionsExport' => $rolePermissionsExport,
                'headerName' => $this->getString('Rol', 'name'),
                'headerDescription' => $this->getString('Descripción', 'description'),
                'headerCreatedAt' => $this->getString('Fecha Creado', 'created_at_human'),
            ])
                ->render();

            $this->startPageGroup();
            $this->AddPage();
            $this->writeHTML($html);
        }

        return $this->Output('REPORTE: ROLES');
    }

    private function getFilters(): array
    {
        $filters = [
            'permissions' => '',
            'search' => '',
        ];

        if (isset($this->filters['permissions']))
        {
            $filters['permissions'] .= Arr::join($this->filters['permissions'], ', ');
        }

        if (isset($this->filters['search']))
        {
            $filters['search'] .= $this->filters['search'];
        }

        return $filters;
    }

    public function getString(string $txt, string $col): string
    {
        if ($col === 'created_at_human')
        {
            if (!isset($this->filters['sort_by']))
            {
                return "↓ {$txt}";
            }
            elseif (isset($this->filters['sort_by']['created_at']))
            {
                return $this->filters['sort_by']['created_at'] === 'asc' ? "↑ {$txt}" : "↓ {$txt}";
            }
            else
            {
                return $txt;
            }
        }

        if (isset($this->filters['sort_by'][$col]))
        {
            return $this->filters['sort_by'][$col] === 'asc' ? "↑ {$txt}" : "↓ {$txt}";
        }

        return $txt;
    }
}
