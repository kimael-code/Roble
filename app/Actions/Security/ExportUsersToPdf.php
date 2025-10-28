<?php

namespace App\Actions\Security;

use App\Models\Organization\Organization;
use App\Models\Security\Permission;
use App\Models\User;
use App\Support\DataExport\BasePdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ExportUsersToPdf extends BasePdf
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
        $this->MultiCell(w: 40, h: 0, align: 'L', border: 'T', ln: 0, txt: 'Usuario(s)');
        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->MultiCell(w: 0, h: 0, align: 'L', border: 'T', ln: 1, txt: $filters['users'] ?: 'Todos');
        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->MultiCell(w: 40, h: 0, align: 'L', border: 'T', ln: 0, txt: 'Estatus');
        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->MultiCell(w: 0, h: 0, align: 'L', border: 'T', ln: 1, txt: $filters['statuses'] ?: 'Todo');
        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->MultiCell(w: 40, h: 0, align: 'L', border: 'T', ln: 0, txt: 'Rol(es)');
        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->MultiCell(w: 0, h: 0, align: 'L', border: 'T', ln: 1, txt: $filters['roles'] ?: 'Todos');
        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->MultiCell(w: 40, h: 0, align: 'L', border: 'T', ln: 0, txt: 'Permisos(s)');
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
        $this->Cell(w: 0, txt: '2. DETALLE DE LOS USUARIOS REGISTRADOS', border: 0, ln: 1, fill: true);
        $this->setTextColor(0, 0, 0);

        // establece el margen superior a la altura ocupada por el header
        $this->tMargin = $this->GetY();
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    }

    public function make(): string
    {
        // metadatos del archivo
        $this->setTitle('REPORTE: USUARIOS');
        $this->setSubject('Reporte de Usuarios registrados');
        $this->setKeywords('reporte, PDF, usuario, usuarios');

        $organizationLogo = Organization::active()->first()->logo_path ?? '';

        $this->setHeaderData(
            ln: storage_path("app/public/{$organizationLogo}"),
            lw: 60,
            ht: 'REPORTE: USUARIOS',
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

        $users = User::filter($this->filters)->get();

        foreach ($users as $key => $user)
        {
            $userPermissionsCreate = $user->getAllPermissions()
                ->filter(fn(Permission $permission) => Str::startsWith($permission->name, 'create'))
                ->sortBy('description')
                ->values()
                ->all();
            $userPermissionsRead = $user->getAllPermissions()
                ->filter(fn(Permission $permission) => Str::startsWith($permission->name, 'read'))
                ->sortBy('description')
                ->values()
                ->all();
            $userPermissionsUpdate = $user->getAllPermissions()
                ->filter(fn(Permission $permission) => Str::startsWith($permission->name, ['enable', 'disable', 'restore', 'update']))
                ->sortBy('description')
                ->values()
                ->all();
            $userPermissionsDelete = $user->getAllPermissions()
                ->filter(fn(Permission $permission) => Str::startsWith($permission->name, ['delete', 'force']))
                ->sortBy('description')
                ->values()
                ->all();
            $userPermissionsExport = $user->getAllPermissions()
                ->filter(fn(Permission $permission) => Str::startsWith($permission->name, 'export'))
                ->sortBy('description')
                ->values()
                ->all();
            $userRoleNames = $user->getRoleNames()->sort()->values()->all();

            $html = View::make('pdf.users.index', [
                'key' => $key,
                'user' => $user,
                'userPermissionsCreate' => $userPermissionsCreate,
                'userPermissionsRead' => $userPermissionsRead,
                'userPermissionsUpdate' => $userPermissionsUpdate,
                'userPermissionsDelete' => $userPermissionsDelete,
                'userPermissionsExport' => $userPermissionsExport,
                'userRoleNames' => $userRoleNames,
                'headerUser' => $this->getString('Usuario', 'name'),
                'headerEmail' => $this->getString('Correo Electrónico', 'email'),
                'headerCreatedAt' => $this->getString('Creado', 'created_at_human'),
                'headerDisabledAt' => $this->getString('Desactivado', 'disabled_at_human'),
                'headerDeletedAt' => $this->getString('Eliminado', 'deleted_at_human'),
            ])
                ->render();

            $this->startPageGroup();
            $this->AddPage();
            $this->writeHTML($html);
        }

        return $this->Output('REPORTE: USUARIOS');
    }

    private function getFilters(): array
    {
        $filters = [
            'permissions' => '',
            'roles' => '',
            'search' => '',
            'statuses' => '',
            'users' => '',
        ];

        if (isset($this->filters['permissions']))
        {
            $filters['permissions'] .= Arr::join($this->filters['permissions'], ', ');
        }

        if (isset($this->filters['roles']))
        {
            $filters['roles'] .= Arr::join($this->filters['roles'], ', ');
        }

        if (isset($this->filters['search']))
        {
            $filters['search'] .= $this->filters['search'];
        }

        if (isset($this->filters['statuses']))
        {
            $filters['statuses'] .= Arr::join($this->filters['statuses'], ', ');
        }

        if (isset($this->filters['users']))
        {
            $filters['users'] .= Arr::join($this->filters['users'], ', ');
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
