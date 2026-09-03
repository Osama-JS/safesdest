<?php

namespace App\Exports\Sheets;

use App\Models\InvestorWalletTransaction;
use App\Models\InvestorWallet;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InvestorInvestmentTransactionsSheet implements FromCollection, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $investorIds;
    protected $totalRecords = 0;
    protected $totalCredit = 0;
    protected $totalDebit = 0;

    public function __construct($fromDate = null, $toDate = null, $investorIds = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->investorIds = $investorIds ? (array)$investorIds : null;
    }

    public function collection()
    {
        $walletQuery = InvestorWallet::query();
        if (!empty($this->investorIds)) {
            $walletQuery->whereIn('user_id', $this->investorIds);
        }
        $walletIds = $walletQuery->pluck('id')->toArray();

        $query = InvestorWalletTransaction::whereIn('investor_wallet_id', $walletIds)
            ->with(['wallet.investor', 'task'])
            ->orderBy('created_at', 'desc');

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay()
            ]);
        }

        $transactions = $query->get();
        $rows = [];
        $seq = 1;

        $sumCredit = 0;
        $sumDebit = 0;

        foreach ($transactions as $t) {
            $investor = $t->wallet?->investor;
            $amount = (float)$t->amount;

            $type = '';
            if ($t->transaction_type === 'credit') {
                $sumCredit += $amount;
                if ($t->source_type === 'refund' || $t->source_type === 'capital_return') {
                    $type = 'استعادة رأس مال';
                } else {
                    $type = 'إيداع رأس مال';
                }
            } else {
                $sumDebit += $amount;
                $type = 'تمويل مهمة';
            }

            $sourceType = match ($t->source_type) {
                'capital_return', 'refund' => 'استعادة استثمار',
                'hyperpay' => 'شحن هايبر باي',
                'capital', 'deposit' => 'إيداع بنكي / رأس مال',
                'manual' => 'تسوية يدوية',
                default => $t->source_type ?? '—'
            };

            $rows[] = [
                'seq' => $seq++,
                'trans_id' => '#' . $t->id,
                'investor_name' => $investor?->name ?? '—',
                'investor_phone' => $investor?->phone ?? '—',
                'wallet_id' => '#' . $t->investor_wallet_id,
                'amount' => number_format($amount, 2),
                'type' => $type,
                'source' => $sourceType,
                'task_id' => $t->task_id ? ('#' . $t->task_id) : '—',
                'description' => $t->description ?? '—',
                'created_at' => $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : '—',
            ];
        }

        $this->totalRecords = count($rows);
        $this->totalCredit = $sumCredit;
        $this->totalDebit = $sumDebit;

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'م',
            'رقم الحركة',
            'اسم المستثمر',
            'رقم الجوال',
            'رقم المحفظة',
            'المبلغ (ر.س)',
            'نوع العملية',
            'المصدر',
            'رقم المهمة المرتبطة',
            'الوصف والبيان',
            'التاريخ والوقت',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // م
            'B' => 14,  // رقم الحركة
            'C' => 24,  // اسم المستثمر
            'D' => 16,  // رقم الجوال
            'E' => 14,  // رقم المحفظة
            'F' => 18,  // المبلغ
            'G' => 18,  // نوع العملية
            'H' => 20,  // المصدر
            'I' => 18,  // رقم المهمة
            'J' => 38,  // الوصف
            'K' => 22,  // التاريخ والوقت
        ];
    }

    public function title(): string
    {
        return 'حركات محافظ الاستثمار';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastCol = 'K';

                // إدراج ترويسة التقرير
                $sheet->insertNewRowBefore(1, 8);

                try {
                    $logoPath = public_path('assets/img/Icon.png');
                    if (file_exists($logoPath)) {
                        $drawing = new Drawing();
                        $drawing->setName('Logo');
                        $drawing->setDescription('وجهات آمنة SafeDests');
                        $drawing->setPath($logoPath);
                        $drawing->setHeight(65);
                        $drawing->setCoordinates('A1');
                        $drawing->setWorksheet($sheet->getDelegate());
                    }
                } catch (\Throwable $e) {
                    // Ignore drawing error
                }

                $sheet->setCellValue('C1', 'منصة وجهات آمنة للخدمات اللوجستية والنقل (SafeDests)');
                $sheet->mergeCells("C1:{$lastCol}1");

                $sheet->setCellValue('C2', 'سجل الحركات المالية لمحافظ الاستثمار (رأس المال والتمويل والاسترداد)');
                $sheet->mergeCells("C2:{$lastCol}2");

                $periodText = ($this->fromDate && $this->toDate)
                    ? "الفترة الزمنية: من {$this->fromDate} إلى {$this->toDate}"
                    : "الفترة الزمنية: كافة الفترات السابقة حتى تاريخه";

                $sheet->setCellValue('C3', $periodText);
                $sheet->mergeCells("C3:{$lastCol}3");

                $sheet->setCellValue('C4', 'تاريخ الاستخراج: ' . Carbon::now()->format('Y-m-d H:i') . ' | إجمالي الإيداعات: ' . number_format($this->totalCredit, 2) . ' ر.س | إجمالي التمويل: ' . number_format($this->totalDebit, 2) . ' ر.س');
                $sheet->mergeCells("C4:{$lastCol}4");

                // تنسيق العناوين
                $sheet->getStyle("C1:{$lastCol}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("C2:{$lastCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '2563EB']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("C3:{$lastCol}4")->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['rgb' => '64748B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // ترويسة الجدول
                $headerRow = 9;
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(30);

                // تنسيق صفوف البيانات
                $dataStartRow = 10;
                $dataEndRow = $dataStartRow + $this->totalRecords - 1;

                if ($this->totalRecords > 0) {
                    $sheet->getStyle("A{$dataStartRow}:{$lastCol}{$dataEndRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    for ($r = $dataStartRow; $r <= $dataEndRow; $r++) {
                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']]
                            ]);
                        }
                        $sheet->getRowDimension($r)->setRowHeight(22);
                    }

                    // صف الإجمالي
                    $summaryRow = $dataEndRow + 1;
                    $sheet->setCellValue("A{$summaryRow}", 'الإجمالي الكلي');
                    $sheet->mergeCells("A{$summaryRow}:E{$summaryRow}");
                    $sheet->setCellValue("F{$summaryRow}", 'إيداعات: ' . number_format($this->totalCredit, 2) . ' | تمويل: ' . number_format($this->totalDebit, 2));
                    $sheet->mergeCells("F{$summaryRow}:{$lastCol}{$summaryRow}");

                    $sheet->getStyle("A{$summaryRow}:{$lastCol}{$summaryRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($summaryRow)->setRowHeight(26);
                }
            }
        ];
    }
}
