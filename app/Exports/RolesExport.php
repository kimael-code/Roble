<?php

namespace App\Exports;

use App\Models\Organization\Organization;
use App\Models\Security\Role;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Exportador de Roles a Excel con encabezado institucional.
 */
class RolesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithDrawings, WithEvents, WithCustomStartCell
{
    private int $headerRows = 9; // Filas para encabezado + filtros

    public function __construct(
        private array $filters = []
    ) {
    }

    public function startCell(): string
    {
        return 'A' . $this->headerRows;
    }

    public function collection(): Collection
    {
        return Role::filter($this->filters)->with('permissions')->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Nombre',
            'Descripción',
            'Fecha Creado',
            'Cantidad de Permisos',
            'Permisos',
        ];
    }

    public function map($role): array
    {
        return [
            $role->id,
            $role->name,
            $role->description,
            $role->created_at?->format('d/m/Y H:i:s'),
            $role->permissions->count(),
            $role->permissions->pluck('description')->join(', '),
        ];
    }

    public function drawings(): Drawing
    {
        $organizationLogo = Organization::active()->first()?->logo_path;
        $logoPath = $organizationLogo
            ? storage_path("app/public/{$organizationLogo}")
            : resource_path('images/logo.png');

        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo Institucional');
        $drawing->setPath($logoPath);
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event)
            {
                $sheet = $event->sheet->getDelegate();

                $generatedBy = auth()->user()?->email ?? 'Sistema';
                $generatedAt = now()->isoFormat('L LTS');

                // Encabezado
                $sheet->setCellValue('C1', 'REPORTE: ROLES');
                $sheet->setCellValue('C2', "Generado: {$generatedAt}");
                $sheet->setCellValue('C3', "Por: {$generatedBy}");

                $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('C2:C3')->getFont()->setSize(10)->setItalic(true);

                // Sección: Filtros Aplicados
                $sheet->setCellValue('A5', 'FILTROS APLICADOS');
                $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A5:F5')->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0'],
                    ],
                ]);

                // Detalle de filtros
                $sheet->setCellValue('A6', 'Buscar:');
                $sheet->setCellValue('B6', $this->filters['search'] ?? 'Todo');
                $sheet->setCellValue('A7', 'Permiso(s):');
                $sheet->setCellValue('B7', !empty($this->filters['permissions']) ? implode(', ', (array) $this->filters['permissions']) : 'Todos');

                $sheet->getStyle('A6:A7')->getFont()->setBold(true);

                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(18);

                $headerRow = $this->headerRows;
                $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '015180']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '8FDBF9'],
                    ],
                ]);

                $lastRow = $sheet->getHighestRow();
                if ($lastRow > $headerRow)
                {
                    $sheet->getStyle("A{$headerRow}:F{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                'color' => ['rgb' => 'CCCCCC'],
                            ],
                        ],
                    ]);
                }
            },
        ];
    }
}
