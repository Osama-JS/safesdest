<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PlatformWalletExport implements FromCollection, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    protected $data;
    protected $statistics;
    protected $filters;

    public function __construct($data, $statistics, $filters)
    {
        $this->data = $data;
        $this->statistics = $statistics;
        $this->filters = $filters;
    }

    /**
     * Return collection of data
     */
    public function collection()
    {
        return collect($this->data)->map(function ($item) {
            return [
                'id' => '#'.$item['id'],
                'customer' => $item['customer'],
                'driver' => $item['driver'],
                'team' => $item['team'],
                'route' => $item['pickup_address'] . ' -> ' . $item['delivery_address'],
                'total_price' => $item['total_price'] . ' SAR',
                'commission' => $item['commission'] . ' SAR',
                'commission_type' => $item['commission_type'],
                'payment_status' => $item['payment_status'],
                'task_status' => $item['task_status'],
                'completed_at' => $item['completed_at']
            ];
        });
    }

    /**
     * Return headings
     */
    public function headings(): array
    {
        return [
            'رقم المهمة',
            'العميل',
            'السائق',
            'الفريق',
            'المسار (من -> إلى)',
            'إجمالي السعر',
            'عمولة المنصة',
            'نوع العمولة',
            'حالة الدفع',
            'حالة المهمة',
            'تاريخ الإكمال'
        ];
    }

    /**
     * Set column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12, // ID
            'B' => 20, // Customer
            'C' => 20, // Driver
            'D' => 15, // Team
            'E' => 40, // Route
            'F' => 15, // Total Price
            'G' => 15, // Commission
            'H' => 15, // Type
            'I' => 15, // Payment Status
            'J' => 15, // Task Status
            'K' => 20, // Completed At
        ];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return 'تقرير محفظة المنصة';
    }

    /**
     * Register events
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                
                // 1. Insert 9 rows at the top (Headings row 1 shifts to row 10)
                $sheet->insertNewRowBefore(1, 9);

                // 2. Add Logo (Manually after inserting rows to keep it at row 1)
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Platform Logo');
                $drawing->setPath(public_path('assets/img/Icon.png'));
                $drawing->setHeight(70);
                $drawing->setCoordinates('D1');
                $drawing->setWorksheet($sheet->getDelegate());

                // 3. Add Header Content
                $sheet->setCellValue('A1', 'شركة SafeDests للنقل والخدمات اللوجستية');
                $sheet->mergeCells('A1:K1');

                $sheet->setCellValue('A2', 'تقرير محفظة المنصة - عمولات المهام');
                $sheet->mergeCells('A2:K2');

                $dateRange = ($this->filters['date_from'] ?? 'N/A') . ' إلى ' . ($this->filters['date_to'] ?? 'N/A');
                $sheet->setCellValue('A3', 'الفترة الزمنية: ' . $dateRange);
                $sheet->mergeCells('A3:K3');

                $sheet->setCellValue('A4', 'تاريخ إنشاء التقرير: ' . date('Y-m-d H:i:s'));
                $sheet->mergeCells('A4:K4');

                // 4. Add Statistics
                $sheet->setCellValue('A6', 'ملخص مالي:');
                $sheet->setCellValue('B6', 'إجمالي العمولات: ' . number_format($this->statistics['total_commissions'], 2) . ' SAR');
                $sheet->setCellValue('D6', 'العمولات المحصلة: ' . number_format($this->statistics['paid_commissions'], 2) . ' SAR');
                $sheet->setCellValue('F6', 'العمولات المعلقة: ' . number_format($this->statistics['pending_commissions'], 2) . ' SAR');
                $sheet->setCellValue('H6', 'نسبة التحصيل: ' . $this->statistics['collection_rate'] . '%');
                
                $sheet->mergeCells('B6:C6');
                $sheet->mergeCells('D6:E6');
                $sheet->mergeCells('F6:G6');
                $sheet->mergeCells('H6:I6');

                $sheet->setCellValue('A7', 'إحصائيات المهام:');
                $sheet->setCellValue('B7', 'عدد المهام الكلي: ' . $this->statistics['total_tasks']);
                $sheet->setCellValue('D7', 'عدد المهام المحصلة: ' . $this->statistics['paid_tasks']);
                $sheet->setCellValue('F7', 'عدد المهام المعلقة: ' . $this->statistics['pending_tasks']);
                
                $sheet->mergeCells('B7:C7');
                $sheet->mergeCells('D7:E7');
                $sheet->mergeCells('F7:G7');

                $sheet->setCellValue('A8', 'توزيع العمولات:');
                $sheet->setCellValue('B8', 'عمولات (Dynamic): ' . number_format($this->statistics['dynamic_commissions'], 2) . ' SAR');
                $sheet->setCellValue('D8', 'عمولات (Manual): ' . number_format($this->statistics['manual_commissions'], 2) . ' SAR');
                
                $sheet->mergeCells('B8:C8');
                $sheet->mergeCells('D8:E8');

                // 5. Style Header and Statistics
                $sheet->getStyle('A1:K8')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ]);

                // Increase height for the logo area
                $sheet->getRowDimension(1)->setRowHeight(40);
                $sheet->getRowDimension(2)->setRowHeight(30);

                $sheet->getStyle('A1:K2')->applyFromArray([
                    'font' => ['size' => 14, 'bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F4FD']
                    ]
                ]);
                
                $sheet->getStyle('A6:K8')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D5E8D4']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ]
                    ]
                ]);

                // 6. Style Table Headings (Now at row 10)
                $sheet->getStyle('A10:K10')->applyFromArray([
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
                ]);

                // 7. Style Table Data and Borders
                $lastDataRow = count($this->data) + 10;
                $sheet->getStyle("A10:K{$lastDataRow}")->applyFromArray([
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
                ]);
            },
        ];
    }
}
