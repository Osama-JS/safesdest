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

    public function __construct(UserWallet $wallet, $transactions)
    {
        $this->wallet = $wallet;
        $this->transactions = $transactions;
        $this->investor = $wallet->user;
    }

    public function collection()
    {
        return collect($this->transactions)->map(function ($trx, $index) {
            return [
                'index' => $index + 1,
                'id' => '#' . $trx->id,
                'type' => $trx->transaction_type === 'credit' ? 'إيداع' : 'خصم',
                'amount' => number_format($trx->amount, 2),
                'description' => $trx->description,
                'date' => $trx->created_at->format('Y-m-d H:i'),
                'balance_after' => number_format($trx->balance_after, 2),
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

                // Add Statistics
                $sheet->setCellValue('A6', 'ملخص مالي:');
                $sheet->setCellValue('B6', 'الرصيد الحالي:');
                $sheet->setCellValue('C6', number_format($this->wallet->balance, 2) . ' SAR');
                
                $sheet->setCellValue('D6', 'إجمالي العمولات المستلمة:');
                $sheet->setCellValue('E6', number_format($totalDeposits, 2) . ' SAR');

                $sheet->setCellValue('F6', 'إجمالي المسحوبات:');
                $sheet->setCellValue('G6', number_format($totalWithdrawals, 2) . ' SAR');

                // Style Header and Statistics
                $sheet->getStyle('A1:G8')->applyFromArray([
                    'font' => ['bold' => true, 'name' => 'Arial'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
                ]);

                $sheet->getRowDimension(1)->setRowHeight(40);
                $sheet->getRowDimension(2)->setRowHeight(30);

                $sheet->getStyle('C1:G2')->applyFromArray([
                    'font' => ['size' => 14, 'bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F4FD']
                    ]
                ]);

                $sheet->getStyle('A6:G6')->applyFromArray([
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
