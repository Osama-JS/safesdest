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

class BrokerCommissionsExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $reportData;
    protected $filters;
    protected $mode;
    protected $selectedColumns;

    public function __construct($reportData, $filters)
    {
        $this->reportData = $reportData;
        $this->filters = $filters;
        $this->mode = $reportData['mode'] ?? ($filters['report_mode'] ?? 'transactions');

        if ($this->mode === 'aggregated') {
            $this->selectedColumns = $filters['columns'] ?? [
                'broker_name',
                'broker_phone',
                'customer_commissions',
                'driver_commissions',
                'task_commissions',
                'investor_commissions',
                'total_commissions',
                'transactions_count',
                'wallet_balance',
            ];
        } else {
            $this->selectedColumns = $filters['columns'] ?? [
                'id',
                'broker_name',
                'source_type',
                'source_name',
                'task_id',
                'amount',
                'description',
                'created_at',
            ];
        }
    }

    /**
     * Return collection of data
     */
    public function collection()
    {
        if ($this->mode === 'aggregated') {
            $brokers = collect($this->reportData['brokers'] ?? []);

            return $brokers->map(function ($item) {
                $row = [];
                foreach ($this->selectedColumns as $col) {
                    switch ($col) {
                        case 'broker_name':
                            $row[] = $item['broker_name'] ?? '-';
                            break;
                        case 'broker_phone':
                            $row[] = $item['broker_phone'] ?? '-';
                            break;
                        case 'broker_email':
                            $row[] = $item['broker_email'] ?? '-';
                            break;
                        case 'customer_commissions':
                            $row[] = number_format((float)($item['customer_commissions'] ?? 0), 2) . ' ' . __('SAR');
                            break;
                        case 'driver_commissions':
                            $row[] = number_format((float)($item['driver_commissions'] ?? 0), 2) . ' ' . __('SAR');
                            break;
                        case 'task_commissions':
                            $row[] = number_format((float)($item['task_commissions'] ?? 0), 2) . ' ' . __('SAR');
                            break;
                        case 'investor_commissions':
                            $row[] = number_format((float)($item['investor_commissions'] ?? 0), 2) . ' ' . __('SAR');
                            break;
                        case 'total_commissions':
                            $row[] = number_format((float)($item['total_commissions'] ?? 0), 2) . ' ' . __('SAR');
                            break;
                        case 'transactions_count':
                            $row[] = $item['transactions_count'] ?? 0;
                            break;
                        case 'wallet_balance':
                            $row[] = number_format((float)($item['wallet_balance'] ?? 0), 2) . ' ' . __('SAR');
                            break;
                        default:
                            $row[] = $item[$col] ?? '-';
                            break;
                    }
                }
                return $row;
            });
        }

        // Transactions Mode
        $transactions = collect($this->reportData['transactions'] ?? []);

        return $transactions->map(function ($item) {
            $row = [];
            foreach ($this->selectedColumns as $col) {
                switch ($col) {
                    case 'id':
                        $row[] = '#' . ($item['sequence'] ?? $item['id']);
                        break;
                    case 'broker_name':
                        $row[] = $item['broker_name'] ?? '-';
                        break;
                    case 'broker_phone':
                        $row[] = $item['broker_phone'] ?? '-';
                        break;
                    case 'source_type':
                        $row[] = $item['source_type_label'] ?? $item['source_type'] ?? '-';
                        break;
                    case 'source_name':
                        $row[] = $item['source_name'] ?? '-';
                        break;
                    case 'task_id':
                        $row[] = !empty($item['task_id']) && $item['task_id'] !== '-' ? '#' . $item['task_id'] : '-';
                        break;
                    case 'amount':
                        $row[] = number_format((float)($item['amount'] ?? 0), 2) . ' ' . __('SAR');
                        break;
                    case 'description':
                        $row[] = $item['description'] ?? '-';
                        break;
                    case 'created_at':
                        $row[] = $item['created_at'] ?? '-';
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
     * Column Headings
     */
    public function headings(): array
    {
        $headers = [];

        $labels = [
            // Aggregated Mode
            'broker_name' => __('Broker Name'),
            'broker_phone' => __('Phone'),
            'broker_email' => __('Email'),
            'customer_commissions' => __('Customer Linking Commissions'),
            'driver_commissions' => __('Driver Linking Commissions'),
            'task_commissions' => __('Direct Task Linking Commissions'),
            'investor_commissions' => __('Investor Linking Commissions'),
            'total_commissions' => __('Total Commissions'),
            'transactions_count' => __('Transactions Count'),
            'wallet_balance' => __('Current Wallet Balance'),

            // Transactions Mode
            'id' => __('Tx #'),
            'source_type' => __('Source Type'),
            'source_name' => __('Source Entity / Name'),
            'task_id' => __('Task #'),
            'amount' => __('Commission Amount'),
            'description' => __('Description'),
            'created_at' => __('Date & Time'),
        ];

        foreach ($this->selectedColumns as $col) {
            $headers[] = $labels[$col] ?? $col;
        }

        return $headers;
    }

    /**
     * Styles
     */
    public function styles(Worksheet $sheet)
    {
        $lastColumnLetter = chr(64 + count($this->selectedColumns));
        $dataCount = $this->mode === 'aggregated'
            ? count($this->reportData['brokers'] ?? [])
            : count($this->reportData['transactions'] ?? []);

        // Table header is at row 9 after 8 header rows
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
                    'startColor' => ['rgb' => '2C3E50']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            "A{$tableHeaderRow}:{$lastColumnLetter}{$lastDataRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D0D7DE']
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
     * Column Widths
     */
    public function columnWidths(): array
    {
        $widths = [];
        $colIndex = 'A';

        foreach ($this->selectedColumns as $col) {
            switch ($col) {
                case 'id':
                case 'task_id':
                case 'transactions_count':
                    $widths[$colIndex] = 15;
                    break;
                case 'broker_name':
                case 'source_name':
                    $widths[$colIndex] = 28;
                    break;
                case 'broker_phone':
                case 'broker_email':
                case 'source_type':
                    $widths[$colIndex] = 22;
                    break;
                case 'amount':
                case 'customer_commissions':
                case 'driver_commissions':
                case 'task_commissions':
                case 'investor_commissions':
                case 'total_commissions':
                case 'wallet_balance':
                    $widths[$colIndex] = 20;
                    break;
                case 'description':
                    $widths[$colIndex] = 35;
                    break;
                case 'created_at':
                    $widths[$colIndex] = 20;
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
        return $this->mode === 'aggregated' ? __('Brokers Aggregates') : __('Brokers Commissions Transactions');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->getDelegate()->setRightToLeft(true);

                $this->addReportHeader($sheet);
                $this->addReportSummary($sheet);
            },
        ];
    }

    /**
     * Add report header info
     */
    private function addReportHeader($sheet)
    {
        $sheet->insertNewRowBefore(1, 8);

        $colCount = count($this->selectedColumns);
        $lastCol = chr(64 + $colCount);

        // 1. Company Name
        $sheet->setCellValue('A1', __('SafeDests Company for Transport and Logistics'));
        $sheet->mergeCells("A1:{$lastCol}1");

        // 2. Report Title
        $reportTitle = $this->mode === 'aggregated'
            ? __('Brokers Commissions Report - Summary Aggregates')
            : __('Brokers Commissions Report - Wallet Recorded Transactions');
        $sheet->setCellValue('A2', $reportTitle);
        $sheet->mergeCells("A2:{$lastCol}2");

        // 3. Broker filter info
        $sheet->setCellValue('A3', __('Brokers:') . ' ' . ($this->reportData['filters_applied']['brokers'] ?? __('All')));
        $sheet->mergeCells("A3:{$lastCol}3");

        // 4. Source filter
        $sheet->setCellValue('A4', __('Commission Source:') . ' ' . ($this->reportData['filters_applied']['source'] ?? __('All')));
        $sheet->mergeCells("A4:{$lastCol}4");

        // 5. Date range
        $sheet->setCellValue('A5', __('Time Period:') . ' ' . ($this->reportData['filters_applied']['date_range'] ?? __('All')));
        $sheet->mergeCells("A5:{$lastCol}5");

        // 6. Generated At
        $genAt = $this->reportData['generated_at'] ? $this->reportData['generated_at']->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        $sheet->setCellValue('A6', __('Creation Date: :date | By: :user', ['date' => $genAt, 'user' => ($this->reportData['generated_by'] ?? __('System'))]));
        $sheet->mergeCells("A6:{$lastCol}6");

        // Row 7 & 8 are spacing
        $sheet->setCellValue('A7', '');
        $sheet->setCellValue('A8', '');

        // Styling Header
        $sheet->getStyle('A1:A6')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);

        $sheet->getStyle("A1:{$lastCol}2")->applyFromArray([
            'font' => ['size' => 13, 'bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EBF5FB']
            ]
        ]);
    }

    /**
     * Add report summary section at bottom
     */
    private function addReportSummary($sheet)
    {
        $dataCount = $this->mode === 'aggregated'
            ? count($this->reportData['brokers'] ?? [])
            : count($this->reportData['transactions'] ?? []);

        $startRow = 9 + max(1, $dataCount) + 2;
        $summary = $this->reportData['summary'] ?? [];

        $sheet->setCellValue("A{$startRow}", __('Brokers Report Statistics Summary:'));
        $sheet->getStyle("A{$startRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '155724']],
        ]);

        $sheet->setCellValue("A" . ($startRow + 1), __('Grand Total Commissions:'));
        $totalVal = $this->mode === 'aggregated'
            ? ($summary['grand_total_commissions'] ?? 0)
            : ($summary['total_commissions'] ?? 0);
        $sheet->setCellValue("B" . ($startRow + 1), number_format($totalVal, 2) . ' ' . __('SAR'));

        $sheet->setCellValue("A" . ($startRow + 2), __('Customer Linking Commissions:'));
        $custVal = $this->mode === 'aggregated'
            ? ($summary['total_customer_commissions'] ?? 0)
            : ($summary['customer_commissions_total'] ?? 0);
        $sheet->setCellValue("B" . ($startRow + 2), number_format($custVal, 2) . ' ' . __('SAR'));

        $sheet->setCellValue("A" . ($startRow + 3), __('Driver Linking Commissions:'));
        $drivVal = $this->mode === 'aggregated'
            ? ($summary['total_driver_commissions'] ?? 0)
            : ($summary['driver_commissions_total'] ?? 0);
        $sheet->setCellValue("B" . ($startRow + 3), number_format($drivVal, 2) . ' ' . __('SAR'));

        $sheet->setCellValue("A" . ($startRow + 4), __('Direct Task Linking Commissions:'));
        $taskVal = $this->mode === 'aggregated'
            ? ($summary['total_task_commissions'] ?? 0)
            : ($summary['task_commissions_total'] ?? 0);
        $sheet->setCellValue("B" . ($startRow + 4), number_format($taskVal, 2) . ' ' . __('SAR'));

        $sheet->setCellValue("A" . ($startRow + 5), __('Total Transactions Count:'));
        $txCount = $this->mode === 'aggregated'
            ? ($summary['total_transactions_count'] ?? 0)
            : ($summary['transactions_count'] ?? 0);
        $sheet->setCellValue("B" . ($startRow + 5), $txCount);

        $sheet->setCellValue("A" . ($startRow + 6), __('Brokers Count in Report:'));
        $brkCount = $this->mode === 'aggregated'
            ? ($summary['total_brokers'] ?? 0)
            : ($summary['brokers_count'] ?? 0);
        $sheet->setCellValue("B" . ($startRow + 6), $brkCount);

        // Style the summary box
        $endRow = $startRow + 6;
        $sheet->getStyle("A{$startRow}:B{$endRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5E9']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'A5D6A7']
                ]
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
    }
}
