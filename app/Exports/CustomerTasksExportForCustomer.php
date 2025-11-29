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
use Illuminate\Support\Collection;

class CustomerTasksExportForCustomer implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $reportData;
    protected $filters;
    protected $customer;

    public function __construct($reportData, $filters, $customer)
    {
        $this->reportData = $reportData;
        $this->filters = $filters;
        $this->customer = $customer;
    }

    /**
     * Return collection of data
     */
    public function collection()
    {
        $tasks = collect($this->reportData['tasks']);

        return $tasks->map(function ($task) {
            return [
                $task['id'],
                $this->formatPrice($task),
                $this->formatPickupInfo($task),
                $this->formatDeliveryInfo($task),
                $task['vehicle_name'] ?? __('Not Specified'),
                $this->formatDriverInfo($task),
                $task['status_ar'] ?? $task['status'],
                $task['payment_status_ar'] ?? $task['payment_status'],
                $task['payment_method_ar'] ?: '',
                $task['created_at_formatted'],
                $task['completed_at_formatted'] ?: __('Not Completed Yet'),
                $task['closed_at_formatted'] ?: __('Not Closed Yet'),
            ];
        });
    }

    /**
     * Format price with refund/cancel handling
     */
    private function formatPrice($task)
    {
        if ($task['total_price'] == 0 && isset($task['original_price']) && $task['original_price'] > 0) {
            return __('Refunded/Canceled - Original Price: :price SAR - Effective Amount: 0.00 SAR', ['price' => number_format($task['original_price'], 2)]);
        }
        return number_format($task['total_price'], 2) . ' ' . __('SAR');
    }

    /**
     * Format pickup information
     */
    private function formatPickupInfo($task)
    {
        return $task['pickup_address'] . "\n" .
               __('Contact Person') . ': ' . ($task['pickup_contact_name'] ?? __('Not Specified')) . "\n" .
               __('Phone') . ': ' . ($task['pickup_contact_phone'] ?? __('Not Specified'));
    }

    /**
     * Format delivery information
     */
    private function formatDeliveryInfo($task)
    {
        return $task['delivery_address'] . "\n" .
               __('Contact Person') . ': ' . ($task['delivery_contact_name'] ?? __('Not Specified')) . "\n" .
               __('Phone') . ': ' . ($task['delivery_contact_phone'] ?? __('Not Specified'));
    }

    /**
     * Format driver information
     */
    private function formatDriverInfo($task)
    {
        return ($task['driver_name'] ?? __('Not Specified')) . "\n" .
               __('Phone') . ': ' . ($task['driver_phone'] ?? __('Not Specified')) . "\n" .
               __('Team') . ': ' . ($task['team_name'] ?? __('Not Specified'));
    }

    /**
     * Return headings
     */
    public function headings(): array
    {
        return [
            __('Task ID'),
            __('Task Price'),
            __('Pickup Information'),
            __('Delivery Information'),
            __('Vehicle Name'),
            __('Driver Information'),
            __('Task Status'),
            __('Payment Status'),
            __('Payment Method'),
            __('Created At'),
            __('Completed At'),
            __('Closed At'),
        ];
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'L'; // 12 columns
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
        return [
            'A' => 12,  // رقم المهمة
            'B' => 20,  // سعر المهمة
            'C' => 25,  // معلومات نقطة الاستلام
            'D' => 25,  // معلومات نقطة التسليم
            'E' => 20,  // اسم المركبة
            'F' => 25,  // معلومات السائق
            'G' => 15,  // حالة المهمة
            'H' => 15,  // حالة الدفع
            'I' => 15,  // طريقة الدفع
            'J' => 18,  // تاريخ الإنشاء
            'K' => 18,  // تاريخ الإكمال
            'L' => 18,  // تاريخ الإغلاق
        ];
    }

    /**
     * Set worksheet title
     */
    public function title(): string
    {
        return __('My Tasks Report');
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
        $sheet->setCellValue('A1', __('SafeDests Transport and Logistics Company'));
        $sheet->mergeCells('A1:L1');

        // Report title
        $sheet->setCellValue('A2', __('Customer Tasks Report - Detailed'));
        $sheet->mergeCells('A2:L2');

        // Customer info
        $customerInfo = __('Customer') . ': ' . $this->customer->name;
        if (!empty($this->customer->company_name)) {
            $customerInfo .= ' - ' . $this->customer->company_name;
        }
        $sheet->setCellValue('A3', $customerInfo);
        $sheet->mergeCells('A3:L3');

        // Date range
        $dateRange = __('Time Period') . ': ';
        if (!empty($this->filters['from_date']) && !empty($this->filters['to_date'])) {
            $dateRange .= $this->filters['from_date'] . ' ' . __('to') . ' ' . $this->filters['to_date'];
        } else {
            $dateRange .= __('All Periods');
        }
        $sheet->setCellValue('A4', $dateRange);
        $sheet->mergeCells('A4:L4');

        // Applied filters
        $appliedFilters = $this->getAppliedFiltersText();
        if (!empty($appliedFilters)) {
            $sheet->setCellValue('A5', __('Applied Filters') . ': ' . $appliedFilters);
            $sheet->mergeCells('A5:L5');
        }

        // Generation info
        $sheet->setCellValue('A6', __('Report Generation Date') . ': ' . now()->format('Y-m-d H:i:s'));
        $sheet->mergeCells('A6:L6');

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
     * Get applied filters as text
     */
    private function getAppliedFiltersText()
    {
        $filters = [];

        if (!empty($this->filters['status']) && is_array($this->filters['status'])) {
            $filters[] = __('Status') . ': ' . implode(', ', $this->filters['status']);
        }

        if (!empty($this->filters['payment_status']) && is_array($this->filters['payment_status'])) {
            $filters[] = __('Payment Status') . ': ' . implode(', ', $this->filters['payment_status']);
        }

        if (!empty($this->filters['payment_method']) && is_array($this->filters['payment_method'])) {
            $filters[] = __('Payment Method') . ': ' . implode(', ', $this->filters['payment_method']);
        }

        return implode(' | ', $filters);
    }

    /**
     * Add report summary at the end
     */
    private function addReportSummary($sheet)
    {
        $lastRow = count($this->reportData['tasks']) + 10; // 8 header rows + 1 data header + 1 for next row

        // Summary title
        $sheet->setCellValue('A' . ($lastRow + 1), __('Report Summary'));
        $sheet->mergeCells('A' . ($lastRow + 1) . ':L' . ($lastRow + 1));

        // Summary data
        $summary = $this->reportData['summary'];
        $sheet->setCellValue('A' . ($lastRow + 2), __('Total Tasks') . ': ' . $summary['total_tasks']);
        $sheet->setCellValue('A' . ($lastRow + 3), __('Total Amount') . ': ' . number_format($summary['total_amount'], 2) . ' ' . __('SAR'));
        $sheet->setCellValue('A' . ($lastRow + 4), __('Average Task Price') . ': ' . number_format($summary['average_amount'], 2) . ' ' . __('SAR'));
        $sheet->setCellValue('A' . ($lastRow + 5), __('Paid Amount') . ': ' . number_format($summary['paid_amount'], 2) . ' ' . __('SAR'));
        $sheet->setCellValue('A' . ($lastRow + 6), __('Pending Amount') . ': ' . number_format($summary['pending_amount'], 2) . ' ' . __('SAR'));

        // Status breakdown
        if (!empty($summary['status_breakdown'])) {
            $sheet->setCellValue('A' . ($lastRow + 8), __('Task Distribution by Status') . ':');
            $row = $lastRow + 9;
            foreach ($summary['status_breakdown'] as $status => $count) {
                $sheet->setCellValue('A' . $row, $status . ': ' . $count);
                $row++;
            }
        }

        // Style summary
        $sheet->getStyle('A' . ($lastRow + 1) . ':A' . ($lastRow + 6))->applyFromArray([
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
