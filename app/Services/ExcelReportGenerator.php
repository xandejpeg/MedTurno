<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelReportGenerator
{
    /**
     * Gera um xlsx a partir de cabeçalho e linhas.
     *
     * @param  list<string>  $headings
     * @param  iterable<int, list<mixed>>  $rows
     */
    public function generate(string $filename, array $headings, iterable $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeçalho
        $sheet->fromArray($headings, null, 'A1');
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
        ]);

        // Linhas
        $sheet->fromArray(array_values(array_map('array_values', iterator_to_array($this->toArray($rows)))), null, 'A2');

        // Auto-largura
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = storage_path('app/reports/'.$filename);
        @mkdir(dirname($path), 0755, true);
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function toArray(iterable $rows): \Generator
    {
        foreach ($rows as $row) {
            yield $row;
        }
    }
}
