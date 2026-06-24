<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StatisticalReportExport implements FromArray, WithStyles, WithTitle, WithEvents
{
    protected $reportData;

    public function __construct($reportData)
    {
        $this->reportData = $reportData;
    }

    public function array(): array
    {
        $rows = [];
        $data = $this->reportData;
        $days = $data['days'];
        $showCurrency = $data['show_currency'];
        $calcNetCommission = $data['calc_net_commission'];

        $formatCur = function($amt) use ($showCurrency) {
            $val = number_format((float)$amt, 2, '.', '');
            return $showCurrency ? $val . ' SAR' : $val;
        };
        $calcTotal = function($arr) {
            return array_sum(array_values($arr));
        };

        // Header Row 1
        $header1 = ['القسم', 'المقاييس / الأيام'];
        foreach ($days as $day) {
            $parts = explode('-', $day);
            $header1[] = $parts[2] . '-' . $parts[1]; // dd-mm
        }
        $header1[] = 'Total';
        $rows[] = $header1;

        $act = $data['activity'];
        $cash = $data['cash'];

        // Activity Section
        // Row 1: Shipments
        $r1 = ['النشاط والربحية', 'Number of Shipments'];
        foreach ($days as $day) $r1[] = $act['shipments'][$day] ?? 0;
        $r1[] = $calcTotal($act['shipments']);
        $rows[] = $r1;

        // Row 2: Active Customers
        $r2 = ['', 'Active Customer'];
        foreach ($days as $day) $r2[] = $act['active_customers'][$day] ?? 0;
        $r2[] = '-';
        $rows[] = $r2;

        // Row 3: Revenue
        $r3 = ['', 'Revenue'];
        foreach ($days as $day) $r3[] = $formatCur($act['revenue'][$day] ?? 0);
        $totalRev = $calcTotal($act['revenue']);
        $r3[] = $formatCur($totalRev);
        $rows[] = $r3;

        // Row 4: Carrier Cost
        $r4 = ['', 'Carrier Cost'];
        foreach ($days as $day) $r4[] = $formatCur($act['carrier_cost'][$day] ?? 0);
        $r4[] = $formatCur($calcTotal($act['carrier_cost']));
        $rows[] = $r4;

        // Row 5: Gross Margin
        $r5 = ['', 'Gross Margin'];
        $totalGross = 0;
        foreach ($days as $day) {
            $margin = ($act['revenue'][$day] ?? 0) - ($act['carrier_cost'][$day] ?? 0);
            $totalGross += $margin;
            $r5[] = $formatCur($margin);
        }
        $r5[] = $formatCur($totalGross);
        $rows[] = $r5;

        // Row 6: Margin %
        $r6 = ['', 'Margin %'];
        foreach ($days as $day) {
            $margin = ($act['revenue'][$day] ?? 0) - ($act['carrier_cost'][$day] ?? 0);
            $rev = $act['revenue'][$day] ?? 0;
            $perc = $rev > 0 ? number_format(($margin / $rev) * 100, 2) . '%' : '0%';
            $r6[] = $perc;
        }
        $totalPerc = $totalRev > 0 ? number_format(($totalGross / $totalRev) * 100, 2) . '%' : '0%';
        $r6[] = $totalPerc;
        $rows[] = $r6;

        // Row 7: Net Commission
        if ($calcNetCommission) {
            $r7 = ['', 'Net Platform Commission'];
            foreach ($days as $day) $r7[] = $formatCur($act['net_commission'][$day] ?? 0);
            $r7[] = $formatCur($calcTotal($act['net_commission']));
            $rows[] = $r7;
        }

        // Cash Section
        // Row 1: Cash Collected
        $rC1 = ['النقدية والتحصيل', 'Cash Collected'];
        foreach ($days as $day) $rC1[] = $formatCur($cash['collected'][$day] ?? 0);
        $rC1[] = $formatCur($calcTotal($cash['collected']));
        $rows[] = $rC1;

        // Row 2: Paid to Carriers
        $rC2 = ['', 'Paid to Carriers'];
        foreach ($days as $day) $rC2[] = $formatCur($cash['paid_to_carriers'][$day] ?? 0);
        $rC2[] = $formatCur($calcTotal($cash['paid_to_carriers']));
        $rows[] = $rC2;

        // Row 3: Cash Gap
        $rC3 = ['', 'Cash Gap'];
        $totalGap = 0;
        foreach ($days as $day) {
            $gap = ($cash['paid_to_carriers'][$day] ?? 0) - ($cash['collected'][$day] ?? 0);
            $totalGap += $gap;
            $rC3[] = $formatCur($gap);
        }
        $rC3[] = $formatCur($totalGap);
        $rows[] = $rC3;

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = chr(65 + count($this->reportData['days']) + 1); // +1 for sections, +1 for metrics, + days, + Total
        // But if days > 24, chr(65 + X) might fail (e.g. AA). So I should better use string increment.
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $styles = [
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
            "A1:{$highestColumn}{$highestRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ]
            ]
        ];
        
        // Bold the second column (metric names)
        $styles["B2:B{$highestRow}"] = [
            'font' => ['bold' => true]
        ];

        // Bold the last column (Totals)
        $styles["{$highestColumn}2:{$highestColumn}{$highestRow}"] = [
            'font' => ['bold' => true]
        ];

        return $styles;
    }

    public function title(): string
    {
        return 'تقرير إحصائية';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->addReportHeader($event->sheet);
            },
        ];
    }

    private function addReportHeader($sheet)
    {
        $sheet->getDelegate()->insertNewRowBefore(1, 8);

        $highestColumn = $sheet->getDelegate()->getHighestColumn();

        // Company name
        $sheet->setCellValue('A1', 'شركة SafeDests للنقل والخدمات اللوجستية');
        $sheet->mergeCells('A1:' . $highestColumn . '1');

        // Report title
        $sheet->setCellValue('A2', 'تقرير إحصائية (مصفوفة) - النشاط والنقدية');
        $sheet->mergeCells('A2:' . $highestColumn . '2');

        // Customer info
        $customerText = 'الجميع';
        if (!empty($this->reportData['filters_applied']['customers'])) {
            $customerText = $this->reportData['filters_applied']['customers'];
        }
        $sheet->setCellValue('A3', 'العميل: ' . $customerText);
        $sheet->mergeCells('A3:' . $highestColumn . '3');

        // Date range
        $dateRangeText = 'من ' . $this->reportData['days'][0] . ' إلى ' . end($this->reportData['days']);
        $sheet->setCellValue('A4', 'الفترة الزمنية: ' . $dateRangeText);
        $sheet->mergeCells('A4:' . $highestColumn . '4');

        // Generation info
        $sheet->setCellValue('A5', 'تاريخ إنشاء التقرير: ' . now()->format('Y-m-d H:i:s'));
        $sheet->mergeCells('A5:' . $highestColumn . '5');

        $sheet->setCellValue('A6', 'أنشئ بواسطة: ' . (auth()->check() ? auth()->user()->name : 'النظام'));
        $sheet->mergeCells('A6:' . $highestColumn . '6');

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
        
        $sheet->getDelegate()->setRightToLeft(false);

        // Merge section cells vertically
        $activityCount = $this->reportData['calc_net_commission'] ? 7 : 6;
        $startActivity = 10;
        $endActivity = $startActivity + $activityCount - 1;
        $sheet->mergeCells("A{$startActivity}:A{$endActivity}");

        $startCash = $endActivity + 1;
        $endCash = $startCash + 2; // Cash has 3 rows
        $sheet->mergeCells("A{$startCash}:A{$endCash}");

        // Center align the merged cells
        $sheet->getStyle("A{$startActivity}:A{$endCash}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);
    }
}
