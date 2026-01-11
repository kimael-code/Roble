<?php

namespace App\Actions\Security;

use App\Models\Organization\Organization;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Support\DataExport\BasePdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;

class ExportPermissionsToPdf extends BasePdf
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

        // Sección 1: Filtros con degradado
        $this->drawSectionHeader('1. FILTROS APLICADOS');

        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->setTextColor(0, 0, 0);
        $this->MultiCell(w: 40, h: 0, align: 'L', ln: 0, txt: 'Buscar');
        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->MultiCell(w: 0, h: 0, align: 'L', ln: 1, txt: $filters['search'] ?: 'Todo');
        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->MultiCell(w: 40, h: 0, align: 'L', border: 'T', ln: 0, txt: 'Usuario(s)');
        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->MultiCell(w: 0, h: 0, align: 'L', border: 'T', ln: 1, txt: $filters['users'] ?: 'Todos');
        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->MultiCell(w: 40, h: 0, align: 'L', border: 'T', ln: 0, txt: 'Rol(es)');
        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->MultiCell(w: 0, h: 0, align: 'L', border: 'T', ln: 1, txt: $filters['roles'] ?: 'Todos');

        $this->setLineStyle([
            'width' => 0.75 / $this->k,
            'cap' => 'butt',
            'join' => 'mitter',
            'dash' => 0,
            'color' => [0, 0, 0],
        ]);

        // Sección 2: Detalle con degradado
        $this->drawSectionHeader('2. DETALLE DE LOS PERMISOS REGISTRADOS');

        // Encabezados de tabla con estilo institucional
        $this->setTableHeaderStyle();
        $this->MultiCell(w: 10, h: 5, align: 'L', ln: 0, txt: '#', fill: true);
        $this->MultiCell(w: 46, h: 5, align: 'L', ln: 0, txt: $this->getString('Nombre', 'name'), fill: true);
        $this->MultiCell(w: 71.7, h: 5, align: 'L', ln: 0, txt: $this->getString('Descripción', 'description'), fill: true);
        $this->MultiCell(w: 30, h: 5, align: 'L', ln: 0, txt: $this->getString('Fecha Creado', 'created_at_human'), fill: true);
        $this->MultiCell(w: 45.5, h: 5, align: 'L', ln: 0, txt: 'Rol/es', fill: true);
        $this->MultiCell(w: 45.5, h: 5, align: 'L', ln: 1, txt: 'Usuario/s', fill: true);

        $this->setTableContentStyle();

        // establece el margen superior a la altura ocupada por el header
        $this->tMargin = $this->GetY();
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    }

    public function make(string $destination = 'I'): string
    {
        // metadatos del archivo
        $this->setTitle('REPORTE: PERMISOS');
        $this->setSubject('Reporte de Permisos registrados');
        $this->setKeywords('reporte, PDF, permiso, permisos');

        $organizationLogo = Organization::active()->first()?->logo_path;
        $imgFile = $organizationLogo ? storage_path("app/public/{$organizationLogo}") : resource_path('images/logo.png');

        $this->setHeaderData(
            ln: $imgFile,
            lw: 60,
            ht: 'REPORTE: PERMISOS',
            hs: now()->isoFormat('L LTS'),
            tc: [29, 38, 53],
            lc: [3, 91, 165],
        );
        $this->setFooterData(
            tc: [29, 38, 53],
            lc: [3, 91, 165],
        );

        $this->setFooterFont(['helvetica', '', 8]);

        $this->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $this->setMargins(PDF_MARGIN_LEFT, 77.5, PDF_MARGIN_RIGHT);
        $this->setHeaderMargin();
        $this->setFooterMargin(15);

        $this->setAutoPageBreak(true, 20);

        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // ---------------------------------------------------------

        $this->setFont(family: 'iosevkafixedss12', size: 8, );

        $this->AddPage();

        $html = View::make('pdf.permissions.index', [
            'permissions' => Permission::filter($this->filters)->get(),
        ]);

        $this->writeHTML($html);

        return $this->Output('REPORTE_PERMISOS.pdf', $destination);
    }

    private function getFilters(): array
    {
        $filters = [
            'roles' => '',
            'users' => '',
            'search' => '',
        ];

        if (isset($this->filters['roles']))
        {
            $filters['roles'] .= Arr::join($this->filters['roles'], ', ');
        }

        if (isset($this->filters['users']))
        {
            $filters['users'] .= Arr::join($this->filters['users'], ', ');
        }

        if (isset($this->filters['search']))
        {
            $filters['search'] .= $this->filters['search'];
        }

        return $filters;
    }

    public function getUsers(Permission $permission): string
    {
        $usersByDirectPermission = $permission->users()->pluck('email')->implode(', ');
        $usersByRoles = $permission->roles->map(
            fn(Role $role) => $role->users->pluck('email')->implode(', ')
        )->implode(', ');

        $users = trim("{$usersByDirectPermission}, {$usersByRoles}", ', ');

        return $users;
    }

    private function getString(string $txt, string $col): string
    {
        if ($col === 'created_at_human')
        {
            if (!isset($this->filters['sort_by']) && empty($this->filters))
            {
                return "↓ {$txt}";
            }
            elseif (!isset($this->filters['sort_by']) && !empty($this->filters))
            {
                return "↑ {$txt}";
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
