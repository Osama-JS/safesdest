<?php

namespace App\Exports;

use App\Models\UserWalletTransaction;
use App\Models\UserWallet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserPersonalWalletExport implements FromCollection, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    protected $wallet;
    protected $transactions;
    protected $user;
    protected $balances = [];

    public function __construct(UserWallet $wallet, $transactions)
    {
        $this->wallet = $wallet;
        $this->transactions = $transactions;
        $this->user = $wallet->user;

        // حساب الرصيد التراكمي التاريخي الدقيق بعد كل عملية للمحفظة
        $allTransactions = UserWalletTransaction::where('user_wallet_id', $this->wallet->id)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'amount', 'transaction_type']);

        $running = 0.0;
        foreach ($allTransactions as $t) {
            $running += ($t->transaction_type === 'credit' ? (float) $t->amount : -(float) $t->amount);
            $this->balances[$t->id] = $running;
        }
    }

    public function collection()
    {
        return collect($this->transactions)->map(function ($trx, $index) {
            $balanceAfter = $this->balances[$trx->id] ?? (float) ($trx->balance_after ?? 0);

            return [
                'index' => $index + 1,
                'id' => '#' . $trx->id,
                'type' => $trx->transaction_type === 'credit' ? 'إحتساب' : 'سحب',
                'amount' => number_format($trx->amount, 2),
                'description' => $trx->description,
                'date' => $trx->created_at->format('Y-m-d H:i'),
                'balance_after' => number_format($balanceAfter, 2),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'م',
            'رقم العملية',
            'النوع',
            'المبلغ (ر.س)',
            'الوصف',
            'تاريخ العملية',
            'الرصيد بعد العملية (ر.س)'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  // index
            'B' => 15, // trx id
            'C' => 12, // type
            'D' => 15, // amount
            'E' => 40, // desc
            'F' => 20, // date
            'G' => 22, // balance after
        ];
    }

    public function title(): string
    {
        return 'محفظة العمولات';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->getDelegate()->setRightToLeft(true);

                // Insert rows at the top for header
                $sheet->insertNewRowBefore(1, 9);

                // Add Logo
                if (file_exists(public_path('assets/img/Icon.png'))) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setDescription('SafeDests Logo');
                    $drawing->setPath(public_path('assets/img/Icon.png'));
                    $drawing->setHeight(70);
                    $drawing->setCoordinates('A1');
                    $drawing->setWorksheet($sheet->getDelegate());
                }

                // Add Header Content
                $sheet->setCellValue('C1', 'شركة SafeDests للنقل والخدمات اللوجستية');
                $sheet->mergeCells('C1:G1');

                $sheet->setCellValue('C2', 'تقرير كشف حساب محفظة العمولات للمستخدم: ' . ($this->user->name ?? 'غير محدد'));
                $sheet->mergeCells('C2:G2');

                $bankInfo = 'الحساب البنكي: ' . ($this->user->bank_name ?: 'غير محدد') . ' | الآيبان: ' . ($this->user->iban_number ?: 'غير محدد');
                $sheet->setCellValue('C3', $bankInfo);
                $sheet->mergeCells('C3:G3');

                $sheet->setCellValue('C4', 'تاريخ إنشاء التقرير: ' . date('Y-m-d H:i:s'));
                $sheet->mergeCells('C4:G4');

                // Calculate Statistics
                $totalCredits = collect($this->transactions)->where('transaction_type', 'credit')->sum('amount');
                $totalDebits = collect($this->transactions)->where('transaction_type', 'debit')->sum('amount');
                $currentBalance = (float) $this->wallet->balance;
                $withdrawableBalance = (float) $this->wallet->withdrawable_balance;

                // Merge cells for financial summary
                $sheet->mergeCells('A6:A7');
                $sheet->mergeCells('B7:D7');
                $sheet->mergeCells('E7:G7');
                $sheet->mergeCells('A8:G8');

                // Row 6: Summary Headers & Values
                $sheet->setCellValue('A6', "ملخص\nمالي");
                $sheet->setCellValue('B6', 'إجمالي العمولات المكتسبة:');
                $sheet->setCellValue('C6', number_format($totalCredits, 2) . ' SAR');

                $sheet->setCellValue('D6', 'إجمالي المسحوبات:');
                $sheet->setCellValue('E6', number_format($totalDebits, 2) . ' SAR');

                $sheet->setCellValue('F6', 'الرصيد الحالي:');
                $sheet->setCellValue('G6', number_format($currentBalance, 2) . ' SAR');

                // Row 7: Withdrawable Balance
                $sheet->setCellValue('B7', 'الرصيد القابل للسحب حالياً:');
                $sheet->setCellValue('E7', number_format($withdrawableBalance, 2) . ' SAR');

                // Row 8: Explanatory note
                $sheet->setCellValue('A8', '* ملاحظة: يتم صرف الحوالات البنكية المباشرة عبر الـ Payout للحساب البنكي المعتمد المسجل في الملف الشخصي.');

                // Style Header and Statistics
                $sheet->getStyle('A1:G8')->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ]);

                $sheet->getRowDimension(1)->setRowHeight(40);
                $sheet->getRowDimension(2)->setRowHeight(30);
                $sheet->getRowDimension(6)->setRowHeight(28);
                $sheet->getRowDimension(7)->setRowHeight(32);
                $sheet->getRowDimension(8)->setRowHeight(22);

                $sheet->getStyle('C1:G2')->applyFromArray([
                    'font' => ['size' => 14, 'bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F4FD']
                    ]
                ]);

                // Summary Side Box (A6:A7)
                $sheet->getStyle('A6:A7')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E293B']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0']
                    ],
                    'alignment' => ['wrapText' => true, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ]);

                // Row 6 styling
                $sheet->getStyle('B6:G6')->applyFromArray([
                    'font' => ['size' => 10, 'bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0FDF4']
                    ]
                ]);
                $sheet->getStyle('B6')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('166534'));
                $sheet->getStyle('C6')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('166534'));
                $sheet->getStyle('D6')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('991B1B'));
                $sheet->getStyle('E6')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('991B1B'));
                $sheet->getStyle('F6')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E40AF'));
                $sheet->getStyle('G6')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E40AF'));

                // Row 7 styling (Withdrawable Balance)
                $sheet->getStyle('B7:G7')->applyFromArray([
                    'font' => ['size' => 11, 'bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'DCFCE7']
                    ]
                ]);
                $sheet->getStyle('B7')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('14532D'));
                $sheet->getStyle('E7')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('15803D'));

                // Row 8 styling (Note)
                $sheet->getStyle('A8:G8')->applyFromArray([
                    'font' => ['size' => 9, 'italic' => true, 'bold' => false, 'color' => ['rgb' => '64748B']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC']
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
                ]);

                // Table Header (Row 10)
                $sheet->getRowDimension(10)->setRowHeight(30);
                $sheet->getStyle('A10:G10')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial', 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E293B']
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ]);

                // Style Data Rows
                $lastRow = $sheet->getHighestRow();
                if ($lastRow >= 11) {
                    $sheet->getStyle('A11:G' . $lastRow)->applyFromArray([
                        'font' => ['name' => 'Arial', 'size' => 10],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'E2E8F0']
                            ]
                        ]
                    ]);

                    for ($row = 11; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(24);
                        if ($row % 2 == 0) {
                            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FAFC']
                                ]
                            ]);
                        }

                        $typeVal = $sheet->getCell('C' . $row)->getValue();
                        if ($typeVal === 'إحتساب') {
                            $sheet->getStyle('C' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('16A34A'))->setBold(true);
                        } elseif ($typeVal === 'سحب') {
                            $sheet->getStyle('C' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DC2626'))->setBold(true);
                        }

                        $sheet->getStyle('G' . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('2563EB'))->setBold(true);
                    }
                }

                // Global Borders for Top Summary Box
                $sheet->getStyle('A6:G8')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);
            }
        ];
    }
}
