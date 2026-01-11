<?php

namespace App\Exports;

use App\Models\Organization\Organization;
use App\Models\Security\Permission;
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
 * Exportador de Permisos a Excel con encabezado institucional.
 */
class PermissionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithDrawings, WithEvents, WithCustomStartCell
{
    private int $headerRows = 10; // Filas para encabezado + filtros

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
        return Permission::filter($this->filters)->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Nombre',
            'Descripción',
            'Fecha Creado',
            'Rol/es',
            'Usuario/s',
        ];
    }

    public function map($permission): array
    {
        return [
            $permission->id,
            $permission->name,
            $permission->description,
            $permission->created_at?->format('d/m/Y H:i:s'),
            $permission->roles->pluck('name')->join(', '),
            $permission->users->pluck('email')->join(', '),
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

                // Encabezado (sin nombre de organización - ya está en el logo)
                $sheet->setCellValue('C1', 'REPORTE: PERMISOS');
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
                $sheet->setCellValue('A7', 'Usuario(s):');
                $sheet->setCellValue('B7', !empty($this->filters['users']) ? implode(', ', (array) $this->filters['users']) : 'Todos');
                $sheet->setCellValue('A8', 'Rol(es):');
                $sheet->setCellValue('B8', !empty($this->filters['roles']) ? implode(', ', (array) $this->filters['roles']) : 'Todos');

                $sheet->getStyle('A6:A8')->getFont()->setBold(true);

                // Altura de filas
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // Estilo de los encabezados de columna
                $headerRow = $this->headerRows;
                $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '015180']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '8FDBF9'],
                    ],
                ]);

                // Bordes para toda la tabla de datos
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
