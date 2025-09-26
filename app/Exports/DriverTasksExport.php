<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DriverTasksExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $reportData;
    protected $filters;
    protected $selectedColumns;

    public function __construct($reportData, $filters)
    {
        $this->reportData = $reportData;
        $this->filters = $filters;
        $this->selectedColumns = $filters['columns'] ?? [];
    }

    /**
     * Return collection of data
     */
    public function collection()
    {
        $tasks = collect($this->reportData['tasks']);

        // Sort tasks by created_at date (newest first) or by id if no date
        $tasks = $tasks->sortByDesc(function ($task) {
            return $task['created_at'] ?? $task['id'] ?? 0;
        });

        return $tasks->map(function ($task) {
            $row = [];

            foreach ($this->selectedColumns as $column) {
                switch ($column) {
                    case 'id':
                        $row[] = '#'.$task['id'];
                        break;

                    case 'task_price':
                        $amount = (float)($task['total_price'] ?? 0);
                        $row[] = number_format($amount, 2) . ' ريال';
                        break;

                    case 'pickup':
                        $row[] =
                            ($task['pickup']['address'] ?? '') . "\n" .
                            'المسؤول: ' . ($task['pickup']['contact_name'] ?? '-') . "\n" .
                            'الهاتف: ' . ($task['pickup']['contact_phone'] ?? '-');
                        break;

                    case 'delivery':
                        $row[] =
                            ($task['delivery']['address'] ?? '') . "\n" .
                            'المسؤول: ' . ($task['delivery']['contact_name'] ?? '-') . "\n" .
                            'الهاتف: ' . ($task['delivery']['contact_phone'] ?? '-');
                        break;

                    case 'vehicle':
                        $row[] = $task['vehicle'] ?? '-';
                        break;

                    case 'driver':
                        $row[] =
                            ($task['driver']['name'] ?? '-') . "\n" .
                            'الهاتف: ' . ($task['driver']['phone'] ?? '-') . "\n" .
                            'الفريق: ' . ($task['team_name'] ?? '-');
                        break;

                    case 'customer':
                        $row[] =
                            ($task['customer']['name'] ?? '-') . "\n" .
                            'الهاتف: ' . ($task['customer']['phone'] ?? '-') . "\n" .
                             ($task['customer']['company_name'] ?? '-');
                        break;

                    case 'status':
                        $row[] =  $task['status'] ?? '-';
                        break;

                    case 'payment_status':
                        $row[] =  $task['payment_status'] ?? '-';
                        break;

                    case 'payment_method':
                        $row[] = $task['payment_status'] == 'completed' ? $task['payment_method'] : '-';
                        break;

                    case 'created_by':
                        $row[] =
                            ($task['created_by'] ?? '-') . "\n" .
                            ($task['created_by_name'] ?? '-');
                        break;

                    case 'created_at':
                        $row[] =  $task['created_at'] ?? '-';
                        break;

                    case 'complated_at':
                        $row[] =  $task['completed_at'] ?? '-';
                        break;
                    case 'closed_at':
                        $row[] =  $task['closed_at'] ?? '-';
                        break;

                    default:
                        $row[] = '';
                        break;
                }
            }

            return $row;
        });
    }


    /**
     * Return headings
     */
    public function headings(): array
    {
        $headings = [];

        foreach ($this->selectedColumns as $column) {
            switch ($column) {
                case 'id':
                    $headings[] = 'رقم المهمة';
                    break;
                case 'task_price':
                    $headings[] = 'المبلغ الصافي';
                    break;
                case 'pickup':
                    $headings[] = 'معلومات نقطة الاستلام';
                    break;
                case 'delivery':
                    $headings[] = 'معلومات نقطة التسليم';

                    break;
                case 'vehicle':
                    $headings[] = 'اسم المركبة';
                    break;
                case 'driver':
                    $headings[] = 'معلومات السائق';
                    break;
                case 'customer':
                    $headings[] = 'معلومات العميل';
                    break;
                case 'status':
                    $headings[] = 'حالة المهمة';
                    break;
                case 'payment_status':
                    $headings[] = 'حالة الدفع';
                    break;
                case 'payment_method':
                    $headings[] = 'طريقة الدفع';
                    break;
                case 'created_by':
                    $headings[] = 'منشئ المهمة';
                    break;
                case 'created_at':
                    $headings[] = 'تاريخ الإنشاء';
                    break;
                case 'complated_at':
                    $headings[] = 'تاريخ الإكمال';
                    break;
                case 'closed_at':
                    $headings[] = 'تاريخ الإغلاق';
                    break;
                default:
                    $headings[] = ucfirst($column);
                    break;
            }
        }


        return $headings;
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = chr(64 + count($this->selectedColumns));
        $lastRow = count($this->reportData['tasks']) + 1;

        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '2C3E50']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

            // All data styling
            "A1:{$lastColumn}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ]
        ];
    }

    /**
     * Set column widths
     */
    public function columnWidths(): array
    {
        $widths = [];
        $columnIndex = 'A';

        foreach ($this->selectedColumns as $column) {
            switch ($column) {
                case 'id':
                    $widths[$columnIndex] = 12;
                    break;

                case 'task_price':
                    $widths[$columnIndex] = 18;
                    break;

                case 'pickup':
                case 'delivery':
                    $widths[$columnIndex] = 30;
                    break;

                case 'vehicle':
                    $widths[$columnIndex] = 20;
                    break;

                case 'driver':
                    $widths[$columnIndex] = 25;
                    break;

                case 'status':
                case 'payment_status':
                case 'payment_method':
                    $widths[$columnIndex] = 15;
                    break;

                case 'created_by':
                    $widths[$columnIndex] = 20;
                    break;

                case 'created_at':
                case 'complated_at':
                case 'closed_at':
                    $widths[$columnIndex] = 18;
                    break;

                default:
                    $widths[$columnIndex] = 15;
                    break;
            }

            $columnIndex++;
        }


        return $widths;
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'تقرير مهام السائق';
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->addReportHeader($event->sheet);
                $this->addReportSummary($event->sheet);
            },
        ];
    }

    /**
     * Add report header with company info
     */
    private function addReportHeader($sheet)
    {
        // Insert rows at the top for header
        $sheet->insertNewRowBefore(1, 8);

        // Company name
        $sheet->setCellValue('A1', 'شركة SafeDests للنقل والخدمات اللوجستية');
        $sheet->mergeCells('A1:' . chr(64 + count($this->selectedColumns)) . '1');

        // Report title
        $sheet->setCellValue('A2', 'تقرير مهام السائق - مفصل');
        $sheet->mergeCells('A2:' . chr(64 + count($this->selectedColumns)) . '2');
        // Driver info
        if (!empty($this->reportData['filters_applied']['drivers'])) {
            $sheet->setCellValue('A3', 'السائق: ' . $this->reportData['filters_applied']['drivers']);
            $sheet->mergeCells('A3:' . chr(64 + count($this->selectedColumns)) . '3');
        }

        // Date range
        $sheet->setCellValue('A4', 'الفترة الزمنية: ' . $this->reportData['filters_applied']['date_range']);
        $sheet->mergeCells('A4:' . chr(64 + count($this->selectedColumns)) . '4');

        // Generation info
        $sheet->setCellValue('A5', 'تاريخ إنشاء التقرير: ' . $this->reportData['generated_at']->format('Y-m-d H:i:s'));
        $sheet->mergeCells('A5:' . chr(64 + count($this->selectedColumns)) . '5');

        $sheet->setCellValue('A6', 'أنشئ بواسطة: ' . $this->reportData['generated_by']);
        $sheet->mergeCells('A6:' . chr(64 + count($this->selectedColumns)) . '6');

        // Empty row
        $sheet->setCellValue('A7', '');

        // Style header
        $sheet->getStyle('A1:A6')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $sheet->getStyle('A1:A2')->applyFromArray([
            'font' => ['size' => 14, 'bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F4FD']
            ]
        ]);
    }

    /**
     * Add report summary at the end
     */
    private function addReportSummary($sheet)
    {
        $lastRow = count($this->reportData['tasks']) + 10; // 8 header rows + 1 data header + 1 for next row

        // Summary title
        $sheet->setCellValue('A' . ($lastRow + 1), 'ملخص التقرير');
        $sheet->mergeCells('A' . ($lastRow + 1) . ':' . chr(64 + count($this->selectedColumns)) . ($lastRow + 1));

        // Summary data
        $sheet->setCellValue('A' . ($lastRow + 2), 'إجمالي عدد المهام: ' . $this->reportData['summary']['total_tasks']);
        $sheet->setCellValue('A' . ($lastRow + 3), 'إجمالي المبلغ الصافي (بعد العمولة): ' . number_format($this->reportData['summary']['total_amount'], 2) . ' ريال');
        $sheet->setCellValue('A' . ($lastRow + 4), 'متوسط سعر المهمة: ' . number_format($this->reportData['summary']['average_amount'], 2) . ' ريال');


        // Style summary
        $sheet->getStyle('A' . ($lastRow + 1) . ':A' . ($lastRow + 5))->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $sheet->getStyle('A' . ($lastRow + 1))->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D5E8D4']
            ]
        ]);
    }
}
