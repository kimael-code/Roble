<?php

namespace App\Support\DataExport;

use App\Models\Organization\Organization;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use TCPDF;

/**
 * Clase base para generación de reportes PDF con estilos institucionales.
 */
class BasePdf extends TCPDF
{
    // Paleta de colores institucionales
    protected const COLOR_AZUL_COBALTO = [1, 81, 128];      // #015180
    protected const COLOR_AZUL_CERULEO = [2, 98, 178];      // #0262b2
    protected const COLOR_AZUL_CLARO = [143, 219, 249];     // #8fdbf9
    protected const COLOR_AZUL_CELESTE = [8, 177, 242];     // #08b1f2
    protected const COLOR_TURQUESA_OSCURO = [0, 104, 127];  // #00687f
    protected const COLOR_TURQUESA = [76, 167, 191];        // #4ca7bf
    protected const COLOR_AZUL_MARINO = [29, 38, 54];       // #1d2636

    public function __construct(
        protected string $orientation = 'P',
        protected string $format = 'A4',
    ) {
        parent::__construct(
            orientation: $orientation,
            format: $format,
        );

        \TCPDF_FONTS::addTTFfont(fontfile: resource_path('fonts/iosevka-33.2.5/IosevkaFixedSS12-Regular.ttf'));
    }

    public function Header()
    {
        $headerData = $this->getHeaderData();

        $organizationLogo = Organization::active()->first()?->logo_path;
        $imgFile = $organizationLogo ? storage_path("app/public/{$organizationLogo}") : resource_path('images/logo.png');

        $this->Image(
            file: $imgFile,
            w: $headerData['logo_width'] ?? 45,
            h: 15,
            type: 'PNG',
            resize: true,
            fitbox: 'LT',
        );

        if (!App::environment('production'))
        {
            // get the current page break margin
            $bMargin = $this->getBreakMargin();
            // get current auto-page-break mode
            $auto_page_break = $this->AutoPageBreak;
            // disable auto-page-break
            $this->SetAutoPageBreak(false, 0);

            $watermark = $this->orientation == 'P' ? 'no-valid-v.png' : 'no-valid-h.png';
            $width = $this->orientation == 'P' ? 216 : 279;
            $height = $this->orientation == 'P' ? 279 : 216;
            $file = resource_path("images/watermarks/$watermark");

            $this->Image(file: $file, w: $width, h: $height);

            // restore auto-page-break status
            $this->SetAutoPageBreak($auto_page_break, $bMargin);
            // set the starting point for the page content
            $this->setPageMark();
        }

        $this->setCreator(PDF_CREATOR);
        $this->setAuthor(config('app.name'));

        $this->setTextColorArray($this->header_text_color);

        $this->setFont(family: 'dejavusans', style: 'B', size: 12);
        $this->MultiCell(w: 0, h: 0, txt: $headerData['title'], align: 'L', x: 80);
        $this->setX(80);
        $this->Cell(w: 0, txt: config('app.name'), ln: 1);

        $this->setFont(family: 'iosevkafixedss12', size: 10);
        $this->Cell(w: 0, txt: "Generado en fecha: {$headerData['string']}", align: 'R', ln: 1);

        // Agregar información del usuario que genera el reporte
        $generatedBy = Auth::check() ? Auth::user()->email : 'Sistema';
        $this->Cell(w: 0, txt: "Por: {$generatedBy}", align: 'R');

        $this->setLineStyle([
            'width' => 0,
            'cap' => 'round',
            'join' => 'round',
            'dash' => 0,
            'color' => $headerData['line_color']
        ]);
        $this->Ln(10);
    }

    /**
     * Pie de página con versión del sistema y paginación.
     */
    public function Footer()
    {
        $this->SetY(-15);
        $this->setFont(family: 'helvetica', size: 8);
        $this->setTextColorArray(self::COLOR_AZUL_MARINO);

        // Línea separadora
        $this->setDrawColorArray(self::COLOR_AZUL_COBALTO);
        $this->Line($this->lMargin, $this->GetY(), $this->w - $this->rMargin, $this->GetY());

        $this->Ln(2);

        // Versión del sistema a la izquierda, paginación a la derecha
        $version = config('app.version', '1.0.0');
        $this->Cell(0, 10, "v{$version}", 0, 0, 'L');
        $this->Cell(0, 10, $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    /**
     * Dibuja un encabezado de sección con degradado institucional.
     *
     * @param string $text Texto del encabezado
     * @param float|null $width Ancho del degradado (null = ancho completo)
     */
    protected function drawSectionHeader(string $text, ?float $width = null): void
    {
        $x = $this->lMargin;
        $y = $this->GetY();
        $w = $width ?? ($this->w - $this->lMargin - $this->rMargin);
        $h = 6;

        // Degradado más contrastante: Azul Marino → Celeste
        // Usando colores con mayor diferencia para que el gradiente sea visible
        $colorInicio = self::COLOR_AZUL_MARINO;   // #1d2636 - muy oscuro
        $colorFin = self::COLOR_AZUL_CELESTE;     // #08b1f2 - muy claro

        $this->LinearGradient(
            $x, $y, $w, $h,
            $colorInicio,
            $colorFin,
            [0, 0, 1, 0] // Dirección: izquierda a derecha
        );

        // Texto sobre el degradado
        $this->SetXY($x, $y);
        $this->setFont(family: 'helvetica', style: 'B', size: 10);
        $this->setTextColor(255, 255, 255);
        $this->Cell(w: $w, h: $h, txt: $text, border: 0, ln: 1, align: 'L', fill: false);
        $this->setTextColor(0, 0, 0);
    }

    /**
     * Configura los estilos para encabezados de tabla.
     */
    protected function setTableHeaderStyle(): void
    {
        $this->setFillColorArray(self::COLOR_AZUL_CLARO);
        $this->setTextColorArray(self::COLOR_AZUL_COBALTO);
        $this->setFont(family: 'dejavusans', style: 'B', size: 9);
    }

    /**
     * Restaura los estilos normales para el contenido de tabla.
     */
    protected function setTableContentStyle(): void
    {
        $this->setFillColor(255, 255, 255);
        $this->setTextColor(0, 0, 0);
        $this->setFont(family: 'iosevkafixedss12', size: 8);
    }
}
