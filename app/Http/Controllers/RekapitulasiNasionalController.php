<?php

namespace App\Http\Controllers;

use App\Services\RekapitulasiNasionalData;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapitulasiNasionalController extends Controller
{
    public function index()
    {
        return view('rekapitulasi.nasional', [
            'title' => 'Rekapitulasi Nasional',
            'heading' => RekapitulasiNasionalData::heading(),
            'subtitle' => RekapitulasiNasionalData::subtitle(),
            'rows' => RekapitulasiNasionalData::rows(),
            'totals' => RekapitulasiNasionalData::totals(),
        ]);
    }

    public function exportPdf()
    {
        return Pdf::loadView('exports.rekapitulasi-nasional-pdf', [
            'heading' => RekapitulasiNasionalData::heading(),
            'subtitle' => RekapitulasiNasionalData::subtitle(),
            'rows' => RekapitulasiNasionalData::rows(),
            'totals' => RekapitulasiNasionalData::totals(),
        ])
            ->setPaper('a4', 'landscape')
            ->download('rekapitulasi-nasional.pdf');
    }

    public function exportExcel(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nasional');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->freezePane('A6');

        $sheet->setCellValue('A1', RekapitulasiNasionalData::heading());
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A2', RekapitulasiNasionalData::subtitle());
        $sheet->mergeCells('A2:K2');

        $sheet->setCellValue('A4', 'NO');
        $sheet->mergeCells('A4:A5');
        $sheet->setCellValue('B4', 'NAMA PROVINSI');
        $sheet->mergeCells('B4:B5');
        $sheet->setCellValue('C4', 'REALISASI PENYEDIA');
        $sheet->mergeCells('C4:D4');
        $sheet->setCellValue('E4', 'PERSENTASE');
        $sheet->mergeCells('E4:E5');
        $sheet->setCellValue('F4', 'REALISASI SWAKELOLA');
        $sheet->mergeCells('F4:G4');
        $sheet->setCellValue('H4', 'PERSENTASE');
        $sheet->mergeCells('H4:H5');
        $sheet->setCellValue('I4', 'REKAPITULASI TOTAL');
        $sheet->mergeCells('I4:J4');
        $sheet->setCellValue('K4', 'PERSENTASE');
        $sheet->mergeCells('K4:K5');
        $sheet->setCellValue('C5', 'REALISASI');
        $sheet->setCellValue('D5', 'PERENCANAAN');
        $sheet->setCellValue('F5', 'REALISASI');
        $sheet->setCellValue('G5', 'PERENCANAAN');
        $sheet->setCellValue('I5', 'REALISASI');
        $sheet->setCellValue('J5', 'PERENCANAAN');

        $columns = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        $rows = RekapitulasiNasionalData::rows();
        $startRow = 6;

        foreach ($rows as $index => $row) {
            $rowNumber = $startRow + $index;
            $sheet->setCellValueExplicit('A' . $rowNumber, (string) ($index + 1), DataType::TYPE_STRING);

            foreach ($columns as $columnIndex => $column) {
                $sheet->setCellValueExplicit($column . $rowNumber, $row[$columnIndex], DataType::TYPE_STRING);
            }
        }

        $totalRow = $startRow + count($rows);
        $totals = RekapitulasiNasionalData::totals();
        $sheet->setCellValueExplicit('B' . $totalRow, 'TOTAL', DataType::TYPE_STRING);

        foreach ($totals as $index => $value) {
            $sheet->setCellValueExplicit($columns[$index] . $totalRow, $value, DataType::TYPE_STRING);
        }

        $this->styleWorksheet($sheet, $totalRow);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer, $spreadsheet): void {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'rekapitulasi-nasional.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function styleWorksheet(Worksheet $sheet, int $totalRow): void
    {
        $sheet->getStyle('A1:K2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);

        $sheet->getStyle('A4:K5')->getFont()->setBold(true);
        $sheet->getStyle('A4:K5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:K5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A4:K5')->getAlignment()->setWrapText(true);

        $sheet->getStyle('A4:K4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('9DC3E6');
        $sheet->getStyle('A5:K5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BDD7EE');

        $sheet->getStyle('A4:K' . $totalRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('A6:A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B6:B' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B6:B' . $totalRow)->getAlignment()->setWrapText(true);
        $sheet->getStyle('C6:K' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A' . $totalRow . ':K' . $totalRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $totalRow . ':K' . $totalRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->getRowDimension(4)->setRowHeight(22);
        $sheet->getRowDimension(5)->setRowHeight(22);

        $widths = [5, 28, 17, 17, 11, 17, 17, 11, 17, 17, 11];

        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
        }
    }
}
