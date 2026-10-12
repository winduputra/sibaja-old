<?php

namespace App\Services;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class LaporanBulananWorkbookService
{
    private const WORKBOOK_FILE = 'Rekapitulasi Pelaporan PBJ Agustus 2026.xlsx';
    private const AVAILABLE_YEAR = 2026;
    private const AVAILABLE_MONTH = 'Agustus';

    private const SHEET_MAP = [
        'rup' => 'RUP',
        'e-kontrak' => 'E-Kontrak',
        'pencatatan-swakelola' => 'Pencatatan Swakelola',
        'pencatatan-non-tender' => 'Pencatatan Non Tender',
        'katalog' => 'Katalog',
        'loka-pasar' => 'Loka Pasar',
    ];

    public static function sheetMap(): array
    {
        return self::SHEET_MAP;
    }

    public static function months(): array
    {
        return ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    }

    public function project(string $sheetSlug, int $tahun = self::AVAILABLE_YEAR, string $bulan = self::AVAILABLE_MONTH): array
    {
        $sheetTitle = $this->resolveSheetTitle($sheetSlug);
        $model = $this->makeBaseModel($sheetSlug, $sheetTitle, $tahun, $bulan);

        if ($tahun !== self::AVAILABLE_YEAR || $bulan !== self::AVAILABLE_MONTH) {
            return $model;
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(false);
        $reader->setLoadSheetsOnly([$sheetTitle]);
        $spreadsheet = $reader->load(base_path(self::WORKBOOK_FILE));
        $sheet = $spreadsheet->getSheetByName($sheetTitle);

        if ($sheet === null) {
            $spreadsheet->disconnectWorksheets();
            return $model;
        }

        $range = $this->resolveLogicalRange($sheet);
        $projected = array_merge($model, [
            'available' => true,
            'range' => $range['coordinate'],
            'workbookSheets' => $spreadsheet->getSheetNames(),
            'columns' => $this->projectColumns($sheet, $range['lastColumnIndex']),
            'rows' => $this->projectRows($sheet, $range['lastColumnIndex'], $range['lastRow']),
            'merges' => $this->projectMerges($sheet, $range['lastColumnIndex'], $range['lastRow']),
            'calculationErrors' => [],
            'excelErrors' => [],
        ]);

        $this->evaluateFormulas($projected);
        $spreadsheet->disconnectWorksheets();

        return $projected;
    }

    private function resolveSheetTitle(string $sheetSlug): string
    {
        if (!array_key_exists($sheetSlug, self::SHEET_MAP)) {
            throw new InvalidArgumentException('Unsupported laporan bulanan sheet slug: ' . $sheetSlug);
        }

        return self::SHEET_MAP[$sheetSlug];
    }

    private function makeBaseModel(string $sheetSlug, string $sheetTitle, int $tahun, string $bulan): array
    {
        return [
            'available' => false,
            'sheetSlug' => $sheetSlug,
            'sheetTitle' => $sheetTitle,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'range' => '',
            'workbookSheets' => [],
            'columns' => [],
            'rows' => [],
            'merges' => [],
            'calculationErrors' => [],
            'excelErrors' => [],
        ];
    }

    private function resolveLogicalRange(Worksheet $sheet): array
    {
        $lastColumnIndex = 1;
        $lastRow = 1;
        foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
            $cell = $sheet->getCell($coordinate);
            $value = $cell->getValue();
            if ($value === null || $value === '') {
                continue;
            }

            list($columnName, $row) = Coordinate::coordinateFromString($coordinate);
            $lastColumnIndex = max($lastColumnIndex, Coordinate::columnIndexFromString($columnName));
            $lastRow = max($lastRow, (int) $row);
        }

        foreach (array_keys($sheet->getMergeCells()) as $mergeRange) {
            list($start, $end) = Coordinate::rangeBoundaries($mergeRange);
            $lastColumnIndex = max($lastColumnIndex, $start[0], $end[0]);
            $lastRow = max($lastRow, $start[1], $end[1]);
        }

        return [
            'lastColumnIndex' => $lastColumnIndex,
            'lastRow' => $lastRow,
            'coordinate' => 'A1:' . Coordinate::stringFromColumnIndex($lastColumnIndex) . $lastRow,
        ];
    }

    private function projectColumns(Worksheet $sheet, int $lastColumnIndex): array
    {
        $columns = [];
        for ($index = 1; $index <= $lastColumnIndex; $index++) {
            $column = Coordinate::stringFromColumnIndex($index);
            $dimension = $sheet->getColumnDimension($column);
            $columns[] = [
                'column' => $column,
                'width' => $dimension->getWidth(),
                'hidden' => $dimension->getVisible() === false,
            ];
        }

        return $columns;
    }

    private function projectRows(Worksheet $sheet, int $lastColumnIndex, int $lastRow): array
    {
        $rows = [];
        for ($rowNumber = 1; $rowNumber <= $lastRow; $rowNumber++) {
            $dimension = $sheet->getRowDimension($rowNumber);
            $rows[] = [
                'row' => $rowNumber,
                'height' => $dimension->getRowHeight(),
                'hidden' => $dimension->getVisible() === false,
                'cells' => $this->projectCells($sheet, $rowNumber, $lastColumnIndex),
            ];
        }

        return $rows;
    }

    private function projectCells(Worksheet $sheet, int $rowNumber, int $lastColumnIndex): array
    {
        $cells = [];
        for ($columnIndex = 1; $columnIndex <= $lastColumnIndex; $columnIndex++) {
            $cell = $sheet->getCellByColumnAndRow($columnIndex, $rowNumber);
            $value = $cell->getValue();
            $formula = is_string($value) && strpos($value, '=') === 0 ? $value : null;
            $calculationError = null;

            try {
                $calculatedValue = $formula === null ? $value : $cell->getCalculatedValue();
                $formattedValue = $cell->getFormattedValue();
            } catch (Throwable $exception) {
                $calculatedValue = null;
                $formattedValue = '';
                $calculationError = $exception->getMessage();
            }

            $cells[] = [
                'coordinate' => $cell->getCoordinate(),
                'value' => $value,
                'formula' => $formula,
                'calculatedValue' => $calculatedValue,
                'formattedValue' => $formattedValue,
                'calculationError' => $calculationError,
                'style' => $this->projectStyle($sheet, $cell->getCoordinate()),
            ];
        }

        return $cells;
    }

    private function projectMerges(Worksheet $sheet, int $lastColumnIndex, int $lastRow): array
    {
        $merges = [];
        foreach (array_keys($sheet->getMergeCells()) as $mergeRange) {
            list($start, $end) = Coordinate::rangeBoundaries($mergeRange);
            if ($start[0] > $lastColumnIndex || $start[1] > $lastRow) {
                continue;
            }

            $merges[] = [
                'coordinate' => Coordinate::stringFromColumnIndex($start[0]) . $start[1],
                'range' => $mergeRange,
                'rowspan' => $end[1] - $start[1] + 1,
                'colspan' => $end[0] - $start[0] + 1,
            ];
        }

        return $merges;
    }

    private function projectStyle(Worksheet $sheet, string $coordinate): array
    {
        $style = $sheet->getStyle($coordinate);
        $font = $style->getFont();
        $fill = $style->getFill();
        $alignment = $style->getAlignment();

        return [
            'font' => [
                'bold' => $font->getBold(),
                'italic' => $font->getItalic(),
                'size' => $font->getSize(),
                'name' => $font->getName(),
                'color' => $font->getColor()->getRGB(),
            ],
            'fill' => [
                'type' => $fill->getFillType(),
                'color' => $fill->getStartColor()->getRGB(),
            ],
            'border' => [
                'top' => [
                    'style' => $style->getBorders()->getTop()->getBorderStyle(),
                    'color' => $style->getBorders()->getTop()->getColor()->getRGB(),
                ],
                'right' => [
                    'style' => $style->getBorders()->getRight()->getBorderStyle(),
                    'color' => $style->getBorders()->getRight()->getColor()->getRGB(),
                ],
                'bottom' => [
                    'style' => $style->getBorders()->getBottom()->getBorderStyle(),
                    'color' => $style->getBorders()->getBottom()->getColor()->getRGB(),
                ],
                'left' => [
                    'style' => $style->getBorders()->getLeft()->getBorderStyle(),
                    'color' => $style->getBorders()->getLeft()->getColor()->getRGB(),
                ],
            ],
            'alignment' => [
                'horizontal' => $alignment->getHorizontal(),
                'vertical' => $alignment->getVertical(),
            ],
            'wrapText' => $alignment->getWrapText(),
        ];
    }

    private function evaluateFormulas(array &$model): void
    {
        foreach ($model['rows'] as $rowIndex => $row) {
            foreach ($row['cells'] as $cellIndex => $cell) {
                if ($cell['calculationError'] !== null) {
                    $model['calculationErrors'][] = [
                        'sheet' => $model['sheetTitle'],
                        'coordinate' => $cell['coordinate'],
                        'formula' => $cell['formula'],
                        'message' => $cell['calculationError'],
                    ];
                }

                foreach (['value', 'calculatedValue', 'formattedValue'] as $field) {
                    if (is_string($cell[$field]) && preg_match('/^#(DIV\/0!|N\/A|NAME\?|NULL!|NUM!|REF!|VALUE!)/', $cell[$field]) === 1) {
                        $model['excelErrors'][] = [
                            'sheet' => $model['sheetTitle'],
                            'coordinate' => $cell['coordinate'],
                            'field' => $field,
                            'value' => $cell[$field],
                        ];
                    }
                }

                unset($model['rows'][$rowIndex]['cells'][$cellIndex]['calculationError']);
            }
        }
    }
}
