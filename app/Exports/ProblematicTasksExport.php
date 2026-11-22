<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ProblematicTasksExport implements FromArray, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $tasks;
    protected $sequence = 1;
    protected $totalRecords = 0;
    protected $totalDebited = 0;
    protected $totalCredited = 0;
    protected $totalLocked = 0;

    public function __construct(array $tasks)
    {
        $this->tasks = $tasks;
        $this->totalRecords = count($tasks);
        foreach ($tasks as $t) {
            $this->totalDebited += floatval($t['total_debited'] ?? 0);
            $this->totalCredited += floatval($t['total_credited'] ?? 0);
            $this->totalLocked += floatval($t['net_locked'] ?? 0);
        }
    }

    public function array(): array
    {
        return $this->tasks;
    }

    public function headings(): array
    {
        return [
            'م',
            'رقم المهمة',
            'المستثمر',
            'العميل',
            'حالة المهمة',
            'سعر المهمة الحالي (SAR)',
            'تمويل مخصوم (SAR)',
            'تسوية مستردة (SAR)',
            'الفارق المعلق (SAR)',
            'نوع الإشكالية',
            'تفاصيل وسبب الإشكالية',
            'من موّل / خصم التمويل',
            'من عدّل سعر المهمة',
            'من سوّى المهمة',
        ];
    }

    public function map($item): array
    {
        $fundersStr = [];
        if (!empty($item['funders'])) {
            foreach ($item['funders'] as $f) {
                $fundersStr[] = "{$f['user']} ({$f['date']} - " . number_format($f['amount'], 2) . " SAR)";
            }
        }

        $priceModsStr = [];
        if (!empty($item['price_changes'])) {
            foreach ($item['price_changes'] as $pc) {
                $priceModsStr[] = "{$pc['user']} ({$pc['description']} - {$pc['date']})";
            }
        }

        $settlersStr = [];
        if (!empty($item['settlers'])) {
            foreach ($item['settlers'] as $s) {
                $settlersStr[] = "{$s['user']} ({$s['date']} - " . number_format($s['amount'], 2) . " SAR)";
            }
        }

        $reasonsStr = !empty($item['issue_reasons']) ? implode(' | ', $item['issue_reasons']) : '';

        return [
            $this->sequence++,
            '#' . $item['task_id'],
            $item['investor_name'] ?? '-',
            $item['customer_name'] ?? '-',
            $item['task_status'] ?? '-',
            number_format(floatval($item['task_price']), 2),
            number_format(floatval($item['total_debited']), 2),
            number_format(floatval($item['total_credited']), 2),
            number_format(floatval($item['net_locked']), 2),
            $item['issue_type'] ?? '-',
            $reasonsStr,
            count($fundersStr) > 0 ? implode("\n", $fundersStr) : '-',
            count($priceModsStr) > 0 ? implode("\n", $priceModsStr) : 'لا توجد سجلات تعديل سعر',
            count($settlersStr) > 0 ? implode("\n", $settlersStr) : 'لم تسوّ بعد',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'N'; // A to N
        $lastRow = $this->totalRecords + 8;

        return [
            // Header row styling
            8 => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],

            // All data styling
            "A8:{$lastColumn}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1']
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

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // م
            'B' => 12,  // رقم المهمة
            'C' => 20,  // المستثمر
            'D' => 22,  // العميل
            'E' => 14,  // حالة المهمة
            'F' => 18,  // سعر المهمة الحالي
            'G' => 18,  // تمويل مخصوم
            'H' => 18,  // تسوية مستردة
            'I' => 18,  // الفارق المعلق
            'J' => 24,  // نوع الإشكالية
            'K' => 45,  // تفاصيل وسبب الإشكالية
            'L' => 30,  // من موّل
            'M' => 35,  // من عدّل السعر
            'N' => 30,  // من سوّى
        ];
    }

    public function title(): string
    {
        return 'المهام ذات الإشكاليات والفروقات';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastCol = 'N';

                // Insert header rows
                $sheet->insertNewRowBefore(1, 7);

                // Title and Header info
                $sheet->setCellValue('A1', 'شركة SafeDests للنقل والخدمات اللوجستية');
                $sheet->mergeCells("A1:{$lastCol}1");

                $sheet->setCellValue('A2', 'تقرير تدقيق ومطابقة المهام ذات الإشكاليات وتعديلات الأسعار');
                $sheet->mergeCells("A2:{$lastCol}2");

                $sheet->setCellValue('A3', 'تاريخ التقرير: ' . now()->format('Y-m-d H:i') . ' | إجمالي المهام المرصودة: ' . $this->totalRecords . ' مهمة');
                $sheet->mergeCells("A3:{$lastCol}3");

                $sheet->setCellValue('A4', 'إجمالي مبالغ التمويل المخصومة: ' . number_format($this->totalDebited, 2) . ' SAR | إجمالي التسويات المستردة: ' . number_format($this->totalCredited, 2) . ' SAR | إجمالي الفروقات المعلقة: ' . number_format($this->totalLocked, 2) . ' SAR');
                $sheet->mergeCells("A4:{$lastCol}4");

                $sheet->setCellValue('A5', 'تنبيه تدقيق: الفروقات ناتجة عن تعديل أسعار المهام بعد التمويل أو تسويات بفروقات أو استرداد بدون خصم مسبق');
                $sheet->mergeCells("A5:{$lastCol}5");

                // Empty rows
                $sheet->setCellValue('A6', '');
                $sheet->setCellValue('A7', '');

                // Styling headers
                $sheet->getStyle('A1:A5')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ]);

                $sheet->getStyle('A1:A2')->applyFromArray([
                    'font' => ['size' => 13, 'bold' => true, 'color' => ['rgb' => '1E293B']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F1F5F9']
                    ]
                ]);

                $sheet->getStyle('A4:A5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7']
                    ]
                ]);

                // Summary at the bottom
                $lastRow = $this->totalRecords + 9;
                $sheet->setCellValue('A' . ($lastRow), 'المجموع الإجمالي');
                $sheet->mergeCells('A' . ($lastRow) . ':E' . ($lastRow));

                $sheet->setCellValue('G' . ($lastRow), number_format($this->totalDebited, 2));
                $sheet->setCellValue('H' . ($lastRow), number_format($this->totalCredited, 2));
                $sheet->setCellValue('I' . ($lastRow), number_format($this->totalLocked, 2));

                $sheet->getStyle('A' . ($lastRow) . ":{$lastCol}" . ($lastRow))->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0']
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '475569']
                        ]
                    ]
                ]);

                // Ensure RTL
                $sheet->getDelegate()->setRightToLeft(true);
            },
        ];
    }
}
