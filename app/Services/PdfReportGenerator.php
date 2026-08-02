<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfReportGenerator
{
    /**
     * Gera o PDF do roadmap completo.
     */
    public function roadmap(): string
    {
        $html = view('reports.pdf.roadmap')->render();
        $path = storage_path('app/reports/roadmap-doctorturn.pdf');
        @mkdir(dirname($path), 0755, true);
        Pdf::loadHTML($html)->setPaper('a4')->save($path);

        return $path;
    }

    /**
     * Gera o PDF da parte financeira.
     */
    public function financeiro(): string
    {
        $html = view('reports.pdf.financeiro')->render();
        $path = storage_path('app/reports/financeiro-doctorturn.pdf');
        @mkdir(dirname($path), 0755, true);
        Pdf::loadHTML($html)->setPaper('a4')->save($path);

        return $path;
    }
}
