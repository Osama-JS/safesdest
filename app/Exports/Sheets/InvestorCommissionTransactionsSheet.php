<?php

namespace App\Exports\Sheets;

use App\Models\UserWalletTransaction;
use App\Models\UserWallet;
use App\Models\User;
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

class InvestorCommissionTransactionsSheet implements FromCollection, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $investorIds;
    protected $totalRecords = 0;
    protected $totalEarned = 0;
    protected $totalWithdrawn = 0;

    public function __construct($fromDate = null, $toDate = null, $investorIds = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->investorIds = $investorIds ? (array)$investorIds : null;
    }

    public function collection()
    {
        // جلب معرفات محافظ المستخدمين الخاصة بالمستثمرين فقط
        $investorsQuery = User::where('investor', true);
        if (!empty($this->investorIds)) {
            $investorsQuery->whereIn('id', $this->investorIds);
        }
        $investorUserIds = $investorsQuery->pluck('id')->toArray();

        $walletIds = UserWallet::whereIn('user_id', $investorUserIds)->pluck('id')->toArray();

        $query = UserWalletTransaction::whereIn('user_wallet_id', $walletIds)
            ->with(['userWallet.user', 'task'])
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

        $sumEarned = 0;
        $sumWithdrawn = 0;

        foreach ($transactions as $t) {
            $investor = $t->userWallet?->user;
            $amount = (float)$t->amount;

            $type = '';
            if ($t->transaction_type === 'credit') {
                $sumEarned += $amount;
                $type = 'أرباح مهمة / إيداع عمولة';
            } else {
                $sumWithdrawn += $amount;
                $type = 'سحب أرباح / تحويل';
            }

            $statusText = $t->status == 1 ? 'مكتملة ومستحقة' : 'قيد المعالجة';

            $rows[] = [
                'seq' => $seq++,
                'trans_id' => '#' . $t->id,
                'investor_name' => $investor?->name ?? '—',
                'investor_phone' => $investor?->phone ?? '—',
                'wallet_id' => '#' . $t->user_wallet_id,
                'amount' => number_format($amount, 2),
                'type' => $type,
                'status' => $statusText,
                'task_id' => $t->task_id ? ('#' . $t->task_id) : '—',
                'description' => $t->description ?? '—',
                'created_at' => $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : '—',
            ];
        }

        $this->totalRecords = count($rows);
        $this->totalEarned = $sumEarned;
        $this->totalWithdrawn = $sumWithdrawn;

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'م',
            'رقم الحركة',
            'اسم المستثمر',
            'رقم الجوال',
            'رقم محفظة العمولات',
            'المبلغ (ر.س)',
            'نوع الحركة',
            'الحالة',
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
            'E' => 18,  // رقم المحفظة
            'F' => 18,  // المبلغ
            'G' => 22,  // نوع الحركة
            'H' => 16,  // الحالة
            'I' => 18,  // رقم المهمة
            'J' => 38,  // الوصف
            'K' => 22,  // التاريخ والوقت
        ];
    }

    public function title(): string
    {
        return 'حركات محافظ العمولات والأرباح';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastCol = 'K';

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

                $sheet->setCellValue('C2', 'سجل أرباح وعمولات المستثمرين وحركات السحب والتحويل');
                $sheet->mergeCells("C2:{$lastCol}2");

                $periodText = ($this->fromDate && $this->toDate)
                    ? "الفترة الزمنية: من {$this->fromDate} إلى {$this->toDate}"
                    : "الفترة الزمنية: كافة الفترات السابقة حتى تاريخه";

                $sheet->setCellValue('C3', $periodText);
                $sheet->mergeCells("C3:{$lastCol}3");

                $sheet->setCellValue('C4', 'تاريخ الاستخراج: ' . Carbon::now()->format('Y-m-d H:i') . ' | إجمالي الأرباح المكتسبة: ' . number_format($this->totalEarned, 2) . ' ر.س | إجمالي السحوبات: ' . number_format($this->totalWithdrawn, 2) . ' ر.س');
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

                // صفوف البيانات
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
                    $sheet->setCellValue("F{$summaryRow}", 'أرباح مكتسبة: ' . number_format($this->totalEarned, 2) . ' | سحوبات: ' . number_format($this->totalWithdrawn, 2));
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
