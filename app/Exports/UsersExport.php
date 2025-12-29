<?php

namespace App\Exports;

use App\Models\Organization\Organization;
use App\Models\User;
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
 * Exportador de Usuarios a Excel con encabezado institucional.
 */
class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithDrawings, WithEvents, WithCustomStartCell
{
    private int $headerRows = 12; // Filas para encabezado + filtros

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
        return User::filter($this->filters)->with('roles')->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'Usuario',
            'Correo Electrónico',
            'Fecha Creado',
            'Desactivado',
            'Eliminado',
            'Rol/es',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->created_at?->format('d/m/Y H:i:s'),
            $user->disabled_at?->format('d/m/Y H:i:s') ?? 'N/A',
            $user->deleted_at?->format('d/m/Y H:i:s') ?? 'N/A',
            $user->getRoleNames()->join(', '),
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
                $sheet->setCellValue('C1', 'REPORTE: USUARIOS');
                $sheet->setCellValue('C2', "Generado: {$generatedAt}");
                $sheet->setCellValue('C3', "Por: {$generatedBy}");

                $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('C2:C3')->getFont()->setSize(10)->setItalic(true);

                // Sección: Filtros Aplicados
                $sheet->setCellValue('A5', 'FILTROS APLICADOS');
                $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A5:G5')->applyFromArray([
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
                $sheet->setCellValue('A8', 'Estatus:');
                $sheet->setCellValue('B8', $this->filters['status'] ?? 'Todo');
                $sheet->setCellValue('A9', 'Rol(es):');
                $sheet->setCellValue('B9', !empty($this->filters['roles']) ? implode(', ', (array) $this->filters['roles']) : 'Todos');
                $sheet->setCellValue('A10', 'Permiso(s):');
                $sheet->setCellValue('B10', !empty($this->filters['permissions']) ? implode(', ', (array) $this->filters['permissions']) : 'Todos');

                $sheet->getStyle('A6:A10')->getFont()->setBold(true);

                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(18);

                $headerRow = $this->headerRows;
                $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '015180']],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '8FDBF9'],
                    ],
                ]);

                $lastRow = $sheet->getHighestRow();
                if ($lastRow > $headerRow)
                {
                    $sheet->getStyle("A{$headerRow}:G{$lastRow}")->applyFromArray([
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
