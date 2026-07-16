<?php

namespace App\Http\Controllers;

use App\Services\PengadaanLangsungService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengadaanLangsungController extends Controller
{
    private PengadaanLangsungService $service;

    public function __construct(PengadaanLangsungService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $tahun = (int) $request->query('tahun', 2026);
        $report = $this->service->report($tahun);

        return view('pengadaan-langsung.index', [
            'title' => 'Pengadaan Langsung',
            'selectedYear' => $report['selectedYear'],
            'years' => $report['years'],
            'rows' => $report['rows'],
            'totalPagu' => $report['totalPagu'],
            'totalFinal' => $report['totalFinal'],
            'apiError' => $report['apiError'],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $tahun = (int) $request->query('tahun', 2026);
        $report = $this->service->report($tahun);
        $selectedYear = $report['selectedYear'];
        $rows = $report['rows'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pengadaan Langsung');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->freezePane('A5');

        $sheet->setCellValue('A1', 'PENGADAAN LANGSUNG TA ' . $selectedYear);
        $sheet->mergeCells('A1:H1');

        $headers = ['No.', 'Perangkat Daerah', 'Nama Paket', 'Kegiatan', 'Penyedia', 'Alamat Penyedia', 'Nilai Pagu', 'Nilai Final'];
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column . '3', $header);
            $sheet->setCellValue($column . '4', (string) ($index + 1));
        }

        $startRow = 5;
        foreach ($rows as $index => $row) {
            $rowNumber = $startRow + $index;
            $sheet->setCellValueExplicit('A' . $rowNumber, (string) ($index + 1), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $rowNumber, $row['perangkat_daerah'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $rowNumber, $row['nama_paket'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rowNumber, $row['kegiatan'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $rowNumber, $row['penyedia'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $rowNumber, $row['alamat_penyedia'], DataType::TYPE_STRING);
            $sheet->setCellValue('G' . $rowNumber, $row['nilai_pagu']);
            $sheet->setCellValue('H' . $rowNumber, $row['nilai_final']);
        }

        $lastRow = max($startRow, $startRow + count($rows) - 1);
        $this->styleWorksheet($sheet, $lastRow);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer, $spreadsheet): void {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'pengadaan-langsung-' . $selectedYear . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function styleWorksheet(Worksheet $sheet, int $lastRow): void
    {
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A3:H4')->getFont()->setBold(true);
        $sheet->getStyle('A3:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3:H4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A3:H4')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A3:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E8FB');
        $sheet->getStyle('A3:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G5:H' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('G5:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('B5:F' . $lastRow)->getAlignment()->setWrapText(true);

        $widths = [6, 30, 36, 18, 28, 32, 18, 18];
        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(chr(65 + $index))->setWidth($width);
        }
    }
}
