<?php

namespace App\Exports;

use App\Models\UserWalletTransaction;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
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

class UserWalletExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $userId;
    protected $fromDate;
    protected $toDate;
    protected $sequence = 1;
    protected $user;
    protected $totalRecords = 0;
    protected $totalCredit = 0;
    protected $totalDebit = 0;

    public function __construct($userId, $fromDate = null, $toDate = null)
    {
        $this->userId = $userId;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->user = User::findOrFail($this->userId);
    }

    public function collection()
    {
        $wallet = $this->user->userWallet;

        if (!$wallet) {
            return collect([]);
        }

        $query = UserWalletTransaction::where('user_wallet_id', $wallet->id)
            ->with(['task', 'task.user', 'user'])
            ->orderBy('created_at', 'asc');

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay()
            ]);
        }

        $data = $query->get();
        
        // Calculate totals for summary
        $this->totalRecords = $data->count();
        $this->totalCredit = $data->where('transaction_type', 'credit')->sum('amount');
        $this->totalDebit = $data->where('transaction_type', 'debit')->sum('amount');

        return $data;
    }

    public function headings(): array
    {
        return [
            'رقم العملية',
            'المبلغ (SAR)',
            'نوع العملية',
            'الوصف',
            'رقم المهمة المرتبطة',
            'مستخدم المهمة',
            'التاريخ والوقت',
        ];
    }

    public function map($transaction): array
    {
        // Format transaction type
        $type = '';
        if ($transaction->transaction_type === 'credit') {
            $type = 'إيداع (عمولة/مكافأة)';
        } else {
            $type = 'سحب';
        }

        // Get Task user or transaction user
        $taskUser = '-';
        if ($transaction->task_id) {
            $taskUser = $transaction->task->user->name ?? 'مستخدم غير موجود';
        }

        return [
            $transaction->sequence,
            number_format($transaction->amount, 2),
            $type,
            $transaction->description,
            $transaction->task_id ?? '-',
            $taskUser,
            $transaction->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = 'G'; // A to G (7 columns)
        $lastRow = $this->totalRecords + 8; // 8 is the start of data rows after header

        return [
            // Header row styling
            8 => [
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
            "A8:{$lastColumn}{$lastRow}" => [
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

    public function columnWidths(): array
    {
        return [
            'A' => 15, // رقم العملية
            'B' => 18, // المبلغ
            'C' => 25, // نوع العملية
            'D' => 45, // الوصف
            'E' => 20, // رقم المهمة
            'F' => 25, // مستخدم المهمة
            'G' => 25, // التاريخ والوقت
        ];
    }

    public function title(): string
    {
        return 'تقرير محفظة العمولات';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->addReportHeader($event->sheet);
                $this->addReportSummary($event->sheet);
            },
        ];
    }

    private function addReportHeader($sheet)
    {
        // Insert rows at the top for header
        $sheet->insertNewRowBefore(1, 7);

        $lastCol = 'G';

        // Company name
        $sheet->setCellValue('A1', 'شركة SafeDests للنقل والخدمات اللوجستية');
        $sheet->mergeCells("A1:{$lastCol}1");

        // Report title
        $sheet->setCellValue('A2', 'تقرير محفظة العمولات والمكافآت');
        $sheet->mergeCells("A2:{$lastCol}2");

        // User info
        $sheet->setCellValue('A3', 'المستخدم: ' . $this->user->name . ' (' . $this->user->email . ')');
        $sheet->mergeCells("A3:{$lastCol}3");

        // Date range
        $dateRange = 'كل الأوقات';
        if ($this->fromDate && $this->toDate) {
            $dateRange = "من: {$this->fromDate} إلى: {$this->toDate}";
        }
        $sheet->setCellValue('A4', 'الفترة الزمنية: ' . $dateRange);
        $sheet->mergeCells("A4:{$lastCol}4");

        // Generation info
        $sheet->setCellValue('A5', 'تاريخ إنشاء التقرير: ' . now()->format('Y-m-d H:i:s'));
        $sheet->mergeCells("A5:{$lastCol}5");

        // Empty row
        $sheet->setCellValue('A6', '');

        // Style header
        $sheet->getStyle('A1:A5')->applyFromArray([
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
        
        // Ensure RTL
        $sheet->getDelegate()->setRightToLeft(true);
    }

    private function addReportSummary($sheet)
    {
        $lastRow = $this->totalRecords + 9;

        // Summary title
        $sheet->setCellValue('A' . ($lastRow + 1), 'ملخص المحفظة (للفترة المحددة)');
        $sheet->mergeCells('A' . ($lastRow + 1) . ':G' . ($lastRow + 1));

        // Summary data
        $sheet->setCellValue('A' . ($lastRow + 2), 'إجمالي العمليات: ' . $this->totalRecords);
        $sheet->setCellValue('A' . ($lastRow + 3), 'إجمالي الإيداعات: ' . number_format($this->totalCredit, 2) . ' SAR');
        $sheet->setCellValue('A' . ($lastRow + 4), 'إجمالي السحوبات/الخصومات: ' . number_format($this->totalDebit, 2) . ' SAR');
        $sheet->setCellValue('A' . ($lastRow + 5), 'الصافي في الفترة: ' . number_format($this->totalCredit - $this->totalDebit, 2) . ' SAR');

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
        
        // Summary borders
        $sheet->getStyle('A' . ($lastRow + 1) . ':G' . ($lastRow + 5))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC']
                ]
            ]
        ]);
        
        $sheet->mergeCells('A' . ($lastRow + 2) . ':G' . ($lastRow + 2));
        $sheet->mergeCells('A' . ($lastRow + 3) . ':G' . ($lastRow + 3));
        $sheet->mergeCells('A' . ($lastRow + 4) . ':G' . ($lastRow + 4));
        $sheet->mergeCells('A' . ($lastRow + 5) . ':G' . ($lastRow + 5));
    }
}
