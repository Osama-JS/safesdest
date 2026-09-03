<?php

namespace App\Exports\Sheets;

use App\Models\Task;
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

class InvestorTasksSheet implements FromCollection, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    protected $fromDate;
    protected $toDate;
    protected $investorIds;
    protected $totalRecords = 0;
    protected $totalPriceSum = 0;
    protected $totalInvestorProfitSum = 0;

    public function __construct($fromDate = null, $toDate = null, $investorIds = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->investorIds = $investorIds ? (array)$investorIds : null;
    }

    public function collection()
    {
        $query = Task::whereNotNull('investor_id')
            ->with(['investor', 'customer', 'driver', 'pickup', 'delivery', 'userWalletTransactions'])
            ->orderBy('created_at', 'desc');

        if (!empty($this->investorIds)) {
            $query->whereIn('investor_id', $this->investorIds);
        }

        if ($this->fromDate && $this->toDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay()
            ]);
        }

        $tasks = $query->get();
        $rows = [];
        $seq = 1;

        $sumTotalPrice = 0;
        $sumProfit = 0;

        foreach ($tasks as $task) {
            $totalPrice = (float)$task->total_price;
            $sumTotalPrice += $totalPrice;

            // العمولة المكتسبة من حركة محفظة العمولات للمستثمر
            $commissionTrans = $task->userWalletTransactions
                ->where('transaction_type', 'credit')
                ->first();
            $investorProfit = $commissionTrans ? (float)$commissionTrans->amount : 0;
            $sumProfit += $investorProfit;

            // أسماء الأطراف
            $investorName = $task->investor?->name ?? '—';
            $customerName = $task->customer?->name ?? ($task->user?->name ?? '—');
            $driverName = $task->driver?->name ?? '—';

            $pickupAddress = $task->pickup?->address ?? '—';
            $deliveryAddress = $task->delivery?->address ?? '—';

            $investorPaymentStatus = match ($task->investor_payment_status) {
                'paid' => 'تم الصرف',
                'pending' => 'قيد الانتظار',
                'refunded' => 'مسترد',
                'none' => 'غير محدد',
                default => $task->investor_payment_status ?? '—'
            };

            $taskStatus = match ($task->status) {
                'completed' => 'مكتملة',
                'in_progress' => 'قيد التنفيذ',
                'canceled' => 'ملغاة',
                'delayed' => 'مؤجلة',
                default => $task->status ?? '—'
            };

            $rows[] = [
                'seq' => $seq++,
                'task_id' => '#' . $task->id,
                'investor_name' => $investorName,
                'customer_name' => $customerName,
                'driver_name' => $driverName,
                'pickup_address' => $pickupAddress,
                'delivery_address' => $deliveryAddress,
                'total_price' => number_format($totalPrice, 2),
                'platform_commission' => number_format((float)$task->commission, 2),
                'investor_profit' => number_format($investorProfit, 2),
                'task_status' => $taskStatus,
                'investor_payment_status' => $investorPaymentStatus,
                'closed' => $task->closed ? 'نعم (مغلقة)' : 'لا (جارية)',
                'created_at' => $task->created_at ? $task->created_at->format('Y-m-d H:i') : '—',
                'closed_at' => $task->closed_at ? Carbon::parse($task->closed_at)->format('Y-m-d H:i') : '—',
            ];
        }

        $this->totalRecords = count($rows);
        $this->totalPriceSum = $sumTotalPrice;
        $this->totalInvestorProfitSum = $sumProfit;

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'م',
            'رقم المهمة',
            'اسم المستثمر',
            'اسم العميل',
            'اسم السائق',
            'موقع الاستلام',
            'موقع التسليم',
            'إجمالي قيمة المهمة (ر.س)',
            'عمولة المنصة (ر.س)',
            'ربح المستثمر المكتسب (ر.س)',
            'حالة المهمة',
            'حالة صرف المستثمر',
            'إقفال المهمة',
            'تاريخ الإنشاء',
            'تاريخ الإقفال',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // م
            'B' => 14,  // رقم المهمة
            'C' => 24,  // المستثمر
            'D' => 24,  // العميل
            'E' => 24,  // السائق
            'F' => 30,  // الاستلام
            'G' => 30,  // التسليم
            'H' => 22,  // قيمة المهمة
            'I' => 20,  // عمولة المنصة
            'J' => 24,  // ربح المستثمر
            'K' => 16,  // حالة المهمة
            'L' => 18,  // حالة الصرف
            'M' => 16,  // الإقفال
            'N' => 18,  // تاريخ الإنشاء
            'O' => 18,  // تاريخ الإقفال
        ];
    }

    public function title(): string
    {
        return 'تفاصيل المهام المستثمرة';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastCol = 'O';

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

                $sheet->setCellValue('C2', 'سجل تفاصيل المهام الممولة والمستثمرة وأرباح المستثمرين');
                $sheet->mergeCells("C2:{$lastCol}2");

                $periodText = ($this->fromDate && $this->toDate)
                    ? "الفترة الزمنية: من {$this->fromDate} إلى {$this->toDate}"
                    : "الفترة الزمنية: كافة الفترات السابقة حتى تاريخه";

                $sheet->setCellValue('C3', $periodText);
                $sheet->mergeCells("C3:{$lastCol}3");

                $sheet->setCellValue('C4', 'تاريخ الاستخراج: ' . Carbon::now()->format('Y-m-d H:i') . ' | إجمالي قيمة المهام: ' . number_format($this->totalPriceSum, 2) . ' ر.س | إجمالي أرباح المستثمرين: ' . number_format($this->totalInvestorProfitSum, 2) . ' ر.س');
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
                    $sheet->mergeCells("A{$summaryRow}:G{$summaryRow}");
                    $sheet->setCellValue("H{$summaryRow}", number_format($this->totalPriceSum, 2));
                    $sheet->setCellValue("J{$summaryRow}", number_format($this->totalInvestorProfitSum, 2));

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
