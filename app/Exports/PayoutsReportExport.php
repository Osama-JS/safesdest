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

class PayoutsReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $reportData;
    protected $filters;
    protected $selectedColumns;

    public function __construct($reportData, $filters = [])
    {
        $this->reportData = $reportData;
        $this->filters = $filters;

        $this->selectedColumns = $filters['columns'] ?? [
            'id',
            'reference_id',
            'payout_id',
            'payout_type',
            'entity_type',
            'entity_name',
            'beneficiary_name',
            'iban',
            'bank_name',
            'amount',
            'status',
            'reason',
            'created_at',
            'approver_name',
        ];
    }

    /**
     * Map dataset to selected columns.
     */
    public function collection()
    {
        $payouts = collect($this->reportData['payouts'] ?? []);

        return $payouts->map(function ($item) {
            $row = [];
            foreach ($this->selectedColumns as $col) {
                switch ($col) {
                    case 'id':
                        $row[] = '#' . ($item['id'] ?? '-');
                        break;
                    case 'reference_id':
                        $row[] = $item['reference_id'] ?? '-';
                        break;
                    case 'payout_id':
                        $row[] = $item['payout_id'] ?? '-';
                        break;
                    case 'payout_section':
                        $row[] = $item['payout_section'] ?? '-';
                        break;
                    case 'payout_type':
                        $row[] = $item['payout_type_label'] ?? $item['payout_type'] ?? '-';
                        break;
                    case 'entity_type':
                        $row[] = $item['entity_type'] ?? '-';
                        break;
                    case 'entity_name':
                        $row[] = $item['entity_name'] ?? '-';
                        break;
                    case 'beneficiary_name':
                        $row[] = $item['beneficiary_name'] ?? '-';
                        break;
                    case 'iban':
                        $row[] = $item['iban'] ?? '-';
                        break;
                    case 'bank_name':
                        $row[] = $item['bank_name'] ?? '-';
                        break;
                    case 'amount':
                        $row[] = number_format((float)($item['amount'] ?? 0), 2) . ' ' . __('SAR');
                        break;
                    case 'status':
                        $row[] = $item['status_label'] ?? $item['status'] ?? '-';
                        break;
                    case 'reason':
                        $row[] = $item['reason'] ?? '-';
                        break;
                    case 'created_at':
                        $row[] = $item['created_at'] ?? '-';
                        break;
                    case 'creator_name':
                        $row[] = $item['creator_name'] ?? '-';
                        break;
                    case 'approver_name':
                        $row[] = $item['approver_name'] ?? '-';
                        break;
                    default:
                        $row[] = $item[$col] ?? '-';
                        break;
                }
            }
            return $row;
        });
    }

    /**
     * Headings for exported columns.
     */
    public function headings(): array
    {
        $headers = [];
        $labels = [
            'id'               => __('المعرف (#)'),
            'reference_id'     => __('مرجع Payout (Reference ID)'),
            'payout_id'        => __('رقم العملية في HyperPay'),
            'payout_section'   => __('قسم الـ Payout (الجهة)'),
            'payout_type'      => __('وصف عملية الـ Payout'),
            'entity_type'      => __('نوع المستفيد'),
            'entity_name'      => __('اسم المستفيد بالنظام'),
            'beneficiary_name' => __('اسم المستفيد البنكي'),
            'iban'             => __('رقم الآيبان (IBAN)'),
            'bank_name'        => __('البنك / السويفت'),
            'amount'           => __('المبلغ'),
            'status'           => __('حالة الـ Payout'),
            'reason'           => __('سبب الفشل / الرفض'),
            'created_at'       => __('تاريخ التحويل'),
            'creator_name'     => __('منشئ الطلب'),
            'approver_name'    => __('المعتمد'),
        ];

        foreach ($this->selectedColumns as $col) {
            $headers[] = $labels[$col] ?? $col;
        }

        return $headers;
    }

    /**
     * Styles for sheet.
     */
    public function styles(Worksheet $sheet)
    {
        $lastCol = chr(64 + count($this->selectedColumns));
        $dataCount = count($this->reportData['payouts'] ?? []);
        $tableHeaderRow = 9;
        $lastDataRow = $tableHeaderRow + max(1, $dataCount);

        return [
            $tableHeaderRow => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E293B'] // Dark Navy Slate
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            "A{$tableHeaderRow}:{$lastCol}{$lastDataRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0']
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
     * Column Widths.
     */
    public function columnWidths(): array
    {
        $widths = [];
        $colIndex = 'A';

        foreach ($this->selectedColumns as $col) {
            switch ($col) {
                case 'id':
                    $widths[$colIndex] = 12;
                    break;
                case 'reference_id':
                case 'payout_id':
                    $widths[$colIndex] = 25;
                    break;
                case 'payout_type':
                case 'entity_type':
                    $widths[$colIndex] = 22;
                    break;
                case 'entity_name':
                case 'beneficiary_name':
                    $widths[$colIndex] = 28;
                    break;
                case 'iban':
                    $widths[$colIndex] = 30;
                    break;
                case 'bank_name':
                    $widths[$colIndex] = 20;
                    break;
                case 'amount':
                    $widths[$colIndex] = 20;
                    break;
                case 'status':
                    $widths[$colIndex] = 18;
                    break;
                case 'reason':
                    $widths[$colIndex] = 35;
                    break;
                case 'created_at':
                    $widths[$colIndex] = 22;
                    break;
                case 'creator_name':
                case 'approver_name':
                    $widths[$colIndex] = 22;
                    break;
                default:
                    $widths[$colIndex] = 18;
                    break;
            }
            $colIndex++;
        }

        return $widths;
    }

    public function title(): string
    {
        return __('تقرير مدفوعات Payout');
    }

    /**
     * Register Excel sheet events for title and KPI headers.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $isRtl = app()->getLocale() === 'ar';
                $sheet->setRightToLeft($isRtl);

                $lastCol = chr(64 + count($this->selectedColumns));
                $summary = $this->reportData['summary'] ?? [];

                // Insert 8 rows above table
                $sheet->insertNewRowBefore(1, 8);

                // Row 1: Company / System Name
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', __('نظام سيف ديست للخدمات اللوجستية والنقل - SafeDest'));
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('2C3E50'));
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Row 2: Report Title
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue('A2', __('تقرير المدفوعات والتحويلات عبر Payout (HyperPay Payouts Report)'));
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('4A5568'));
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Row 3: Generation Date & Filter Info
                $sheet->mergeCells("A3:{$lastCol}3");
                $dateInfo = __('تاريخ وتوقيت التوليد: ') . now()->format('Y-m-d H:i:s');
                if (!empty($this->filters['date_from']) || !empty($this->filters['date_to'])) {
                    $dateInfo .= ' | ' . __('الفترة: من ') . ($this->filters['date_from'] ?? '—') . ' ' . __('إلى ') . ($this->filters['date_to'] ?? '—');
                }
                $sheet->setCellValue('A3', $dateInfo);
                $sheet->getStyle('A3')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('718096'));
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Rows 5-7: KPI Summary Block
                // Total Count & Total Amount
                $sheet->setCellValue('A5', __('إجمالي العمليات'));
                $sheet->setCellValue('A6', ($summary['total_count'] ?? 0) . ' ' . __('عملية'));
                $sheet->setCellValue('B5', __('إجمالي المبالغ'));
                $sheet->setCellValue('B6', number_format($summary['total_amount'] ?? 0, 2) . ' ' . __('SAR'));

                // Completed
                $sheet->setCellValue('C5', __('العمليات الناجحة'));
                $sheet->setCellValue('C6', ($summary['completed_count'] ?? 0) . ' (' . number_format($summary['completed_amount'] ?? 0, 2) . ' ' . __('SAR') . ')');

                // Failed
                $sheet->setCellValue('D5', __('العمليات الفاشلة'));
                $sheet->setCellValue('D6', ($summary['failed_count'] ?? 0) . ' (' . number_format($summary['failed_amount'] ?? 0, 2) . ' ' . __('SAR') . ')');

                // Rejected
                if (count($this->selectedColumns) >= 5) {
                    $sheet->setCellValue('E5', __('العمليات المرفوضة'));
                    $sheet->setCellValue('E6', ($summary['rejected_count'] ?? 0) . ' (' . number_format($summary['rejected_amount'] ?? 0, 2) . ' ' . __('SAR') . ')');
                }

                // Style KPI Block
                $kpiEndCol = count($this->selectedColumns) >= 5 ? 'E' : 'D';
                $sheet->getStyle("A5:{$kpiEndCol}5")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '475569']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A6:{$kpiEndCol}6")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("A5:{$kpiEndCol}6")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]]
                ]);

                // Append Total / Summary Row at the bottom
                $dataCount = count($this->reportData['payouts'] ?? []);
                $lastRow = 9 + max(1, $dataCount) + 1;

                $sheet->setCellValue("A{$lastRow}", __('الإجمالي'));
                $sheet->mergeCells("A{$lastRow}:C{$lastRow}");
                $sheet->setCellValue("D{$lastRow}", ($summary['total_count'] ?? 0) . ' ' . __('عملية'));
                
                // Find amount column index
                $amountColIndex = array_search('amount', $this->selectedColumns);
                if ($amountColIndex !== false) {
                    $amountLetter = chr(65 + $amountColIndex);
                    $sheet->setCellValue("{$amountLetter}{$lastRow}", number_format($summary['total_amount'] ?? 0, 2) . ' ' . __('SAR'));
                }

                $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']]]
                ]);
            }
        ];
    }
}
