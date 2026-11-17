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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InvestorPersonalWalletExport implements FromCollection, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    protected $wallet;
    protected $transactions;
    protected $investor;
    protected $balances = [];

    public function __construct(UserWallet $wallet, $transactions)
    {
        $this->wallet = $wallet;
        $this->transactions = $transactions;
        $this->investor = $wallet->user;

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
                'type' => $trx->transaction_type === 'credit' ? 'إحتساب' : 'خصم',
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

                $sheet->setCellValue('C2', 'تقرير محفظة العمولات (الشخصية) للمستثمر: ' . ($this->investor->name ?? 'غير محدد'));
                $sheet->mergeCells('C2:G2');

                $sheet->setCellValue('C3', 'رقم المحفظة: ' . $this->wallet->id);
                $sheet->mergeCells('C3:G3');

                $sheet->setCellValue('C4', 'تاريخ إنشاء التقرير: ' . date('Y-m-d H:i:s'));
                $sheet->mergeCells('C4:G4');

                // Calculate Statistics
                $totalDeposits = collect($this->transactions)->where('transaction_type', 'credit')->sum('amount');
                $totalWithdrawals = collect($this->transactions)->where('transaction_type', 'debit')->sum('amount');
                $currentBalance = (float) $this->wallet->balance;
                $withdrawableBalance = (float) ($this->wallet->withdrawable_balance ?? 0);

                // Merge cells for financial summary
                $sheet->mergeCells('A6:A7');
                $sheet->mergeCells('B7:D7');
                $sheet->mergeCells('E7:G7');
                $sheet->mergeCells('A8:G8');

                // Row 6: Summary Headers & Values (إجمالي العمولات، إجمالي المسحوبات، الرصيد الحالي)
                $sheet->setCellValue('A6', "ملخص\nمالي");
                $sheet->setCellValue('B6', 'إجمالي العمولات المستلمة:');
                $sheet->setCellValue('C6', number_format($totalDeposits, 2) . ' SAR');

                $sheet->setCellValue('D6', 'إجمالي المسحوبات:');
                $sheet->setCellValue('E6', number_format($totalWithdrawals, 2) . ' SAR');

                $sheet->setCellValue('F6', 'الرصيد الحالي:');
                $sheet->setCellValue('G6', number_format($currentBalance, 2) . ' SAR');

                // Row 7: Withdrawable Balance (الرصيد القابل للسحب حالياً)
                $sheet->setCellValue('B7', 'الرصيد القابل للسحب حالياً:');
                $sheet->setCellValue('E7', number_format($withdrawableBalance, 2) . ' SAR');

                // Row 8: Explanatory note
                $sheet->setCellValue('A8', '* ملاحظة: الرصيد القابل للسحب يمثل العمولات المسواة والمتاحة للصرف الفعلي، بينما الرصيد الحالي يمثل إجمالي الرصيد الدفتري (العمولات المستلمة − المسحوبات).');

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
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);

                // Row 6 styling
                $sheet->getStyle('B6:G6')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F1F5F9']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1']
                        ]
                    ]
                ]);
                $sheet->getStyle('C6')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => '059669']]]);
                $sheet->getStyle('E6')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']]]);
                $sheet->getStyle('G6')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => '2563EB']]]);

                // Row 7 (Withdrawable Balance) styling
                $sheet->getStyle('B7:D7')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '065F46']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D1FAE5']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'A7F3D0']
                        ]
                    ]
                ]);

                $sheet->getStyle('E7:G7')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '047857']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'A7F3D0']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '10B981']
                        ]
                    ]
                ]);

                // Row 8 (Note) styling
                $sheet->getStyle('A8:G8')->applyFromArray([
                    'font' => ['bold' => false, 'italic' => true, 'size' => 9.5, 'color' => ['rgb' => '64748B']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8FAFC']
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E2E8F0']
                        ]
                    ]
                ]);

                // Style Table Headings (row 10)
                $sheet->getStyle('A10:G10')->applyFromArray([
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

                // Style Table Data and Borders
                $lastDataRow = count($this->transactions) + 10;
                if(count($this->transactions) > 0) {
                    $sheet->getStyle("A10:G{$lastDataRow}")->applyFromArray([
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
                }
            },
        ];
    }
}
