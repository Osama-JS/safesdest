<?php

namespace App\Exports;

use App\Models\Task;
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

class InvestorFundedTasksExport implements FromCollection, WithHeadings, WithColumnWidths, WithTitle, WithEvents
{
    protected $tasks;
    protected $investor;
    protected $contract;
    protected $walletId;

    public function __construct($tasks, $investor, $contract = null, $walletId = null)
    {
        $this->tasks    = $tasks;
        $this->investor = $investor;
        $this->contract = $contract;
        $this->walletId = $walletId;
    }

    public function collection()
    {
        return collect($this->tasks)->map(function ($task, $index) {
            // العمولة المكتسبة من محفظة العمولات
            $commissionTrans = $task->userWalletTransactions->first();
            $commission      = $commissionTrans ? number_format($commissionTrans->amount, 2) : '—';

            // عنوان الاستلام والتسليم
            $pickupAddress   = $task->pickup?->address   ?? '—';
            $deliveryAddress = $task->delivery?->address ?? '—';

            // نوع المركبة
            $vehicle = '—';
            if ($task->vehicle_size) {
                $vehicle = trim(
                    ($task->vehicle_size->type->name ?? '') . ' - ' .
                    ($task->vehicle_size->name ?? '')
                , ' -');
            }

            // حالة المهمة
            $status = $task->closed ? 'مغلقة' : 'ممولة / جارية';

            // إجمالي المنصة
            $platformComm = (float) ($task->ad->service_commission ?? $task->commission ?? 0);

            // العمولة المتوقعة للمستثمر
            $expectedComm = '—';
            if ($this->contract) {
                $expected = $this->contract->commission_type === 'percentage'
                    ? min(($platformComm * $this->contract->commission_value / 100), $platformComm)
                    : min((float) $this->contract->commission_value, $platformComm);
                $expectedComm = number_format($expected, 2);
            }

            return [
                'index'           => $index + 1,
                'task_id'         => '#' . $task->id,
                'funding_date'    => $task->updated_at->format('Y-m-d'),
                'funding_time'    => $task->updated_at->format('H:i'),
                'customer'        => $task->customer?->name ?? '—',
                'pickup'          => $pickupAddress,
                'delivery'        => $deliveryAddress,
                'vehicle'         => $vehicle,
                'total_price'     => number_format($task->total_price, 2),
                'platform_comm'   => number_format($platformComm, 2),
                'expected_comm'   => $expectedComm,
                'earned_comm'     => $commission,
                'status'          => $status,
                'task_status'     => $task->status ?? '—',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'م',
            'رقم المهمة',
            'تاريخ التمويل',
            'وقت التمويل',
            'العميل',
            'نقطة الاستلام',
            'نقطة التسليم',
            'نوع المركبة',
            'قيمة المهمة (ر.س)',
            'عمولة المنصة (ر.س)',
            'العمولة المتوقعة (ر.س)',
            'العمولة المكتسبة (ر.س)',
            'حالة التمويل',
            'حالة المهمة',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // م
            'B' => 14,  // رقم المهمة
            'C' => 16,  // تاريخ التمويل
            'D' => 12,  // وقت التمويل
            'E' => 22,  // العميل
            'F' => 32,  // الاستلام
            'G' => 32,  // التسليم
            'H' => 22,  // المركبة
            'I' => 18,  // قيمة المهمة
            'J' => 20,  // عمولة المنصة
            'K' => 22,  // العمولة المتوقعة
            'L' => 22,  // العمولة المكتسبة
            'M' => 16,  // حالة التمويل
            'N' => 18,  // حالة المهمة
        ];
    }

    public function title(): string
    {
        return 'سجل المهام الممولة';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet;
                $lastCol   = 'N';
                $colCount  = 14;

                $sheet->getDelegate()->setRightToLeft(true);

                // ── إدراج صفوف للترويسة ──────────────────────────
                $sheet->insertNewRowBefore(1, 10);

                // ── شعار الشركة ──────────────────────────────────
                $logoPath = public_path('assets/img/Icon.png');
                if (file_exists($logoPath)) {
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setDescription('SafeDests Logo');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(70);
                    $drawing->setCoordinates('A1');
                    $drawing->setWorksheet($sheet->getDelegate());
                }

                // ── معلومات التقرير ──────────────────────────────
                $sheet->setCellValue('C1', 'شركة SafeDests للنقل والخدمات اللوجستية');
                $sheet->mergeCells("C1:{$lastCol}1");

                $sheet->setCellValue('C2', 'تقرير المهام الممولة - المستثمر: ' . ($this->investor->name ?? 'غير محدد'));
                $sheet->mergeCells("C2:{$lastCol}2");

                $sheet->setCellValue('C3', 'رقم المستثمر (ID): ' . ($this->investor->id ?? '—'));
                $sheet->mergeCells("C3:G3");

                $sheet->setCellValue('H3', 'تاريخ إنشاء التقرير: ' . date('Y-m-d H:i:s'));
                $sheet->mergeCells("H3:{$lastCol}3");

                if ($this->contract) {
                    $commType = $this->contract->commission_type === 'percentage'
                        ? $this->contract->commission_value . '%'
                        : number_format($this->contract->commission_value, 2) . ' ر.س ثابت';
                    $sheet->setCellValue('C4', 'نوع العقد: ' . ($this->contract->contract_type === 'task_investment' ? 'مستثمر بالمهام' : 'مستثمر عام'));
                    $sheet->mergeCells("C4:G4");
                    $sheet->setCellValue('H4', 'نسبة/قيمة العمولة: ' . $commType);
                    $sheet->mergeCells("H4:{$lastCol}4");
                }

                // ── ملخص مالي ────────────────────────────────────
                $totalFunded   = collect($this->tasks)->sum('total_price');
                $totalEarned   = collect($this->tasks)->sum(function($t) {
                    return $t->userWalletTransactions->first()?->amount ?? 0;
                });
                $totalTasks    = collect($this->tasks)->count();
                $closedTasks   = collect($this->tasks)->where('closed', true)->count();

                $sheet->setCellValue('A6', 'إجمالي المهام:');
                $sheet->setCellValue('B6', $totalTasks . ' مهمة');
                $sheet->mergeCells('B6:C6');

                $sheet->setCellValue('D6', 'مهام مغلقة:');
                $sheet->setCellValue('E6', $closedTasks . ' مهمة');

                $sheet->setCellValue('F6', 'إجمالي القيم الممولة:');
                $sheet->setCellValue('G6', number_format($totalFunded, 2) . ' ر.س');
                $sheet->mergeCells('G6:H6');

                $sheet->setCellValue('I6', 'إجمالي العمولات المكتسبة:');
                $sheet->mergeCells('I6:J6');
                $sheet->setCellValue('K6', number_format($totalEarned, 2) . ' ر.س');
                $sheet->mergeCells("K6:{$lastCol}6");

                // ── تنسيق خلايا الترويسة ─────────────────────────
                // اسم الشركة (أول سطرين)
                $sheet->getStyle("C1:{$lastCol}2")->applyFromArray([
                    'font' => ['size' => 14, 'bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1B2A4A'],
                    ],
                    'font'      => ['color' => ['rgb' => 'FFFFFF'], 'size' => 13, 'bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(45);
                $sheet->getRowDimension(2)->setRowHeight(28);

                // معلومات العقد والتاريخ
                $sheet->getStyle("A3:{$lastCol}5")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EBF3FD'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'font' => ['size' => 10],
                ]);

                // صف الملخص المالي
                $sheet->getStyle("A6:{$lastCol}6")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D5E8D4'],
                    ],
                    'font'      => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '8DC87A']],
                    ],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(22);

                // ── تنسيق رأس الجدول (الصف 11) ──────────────────
                $sheet->getStyle("A11:{$lastCol}11")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 10,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2C3E50'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);
                $sheet->getRowDimension(11)->setRowHeight(24);

                // ── تنسيق بيانات الجدول ───────────────────────────
                $lastDataRow = count($this->tasks) + 11;
                if (count($this->tasks) > 0) {
                    // تلوين الصفوف بالتناوب
                    for ($row = 12; $row <= $lastDataRow; $row++) {
                        $fillColor = ($row % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $fillColor],
                            ],
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(18);
                    }

                    // حدود الجدول الكاملة
                    $sheet->getStyle("A11:{$lastCol}{$lastDataRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['rgb' => 'D5DCE4'],
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'wrapText'   => true,
                        ],
                        'font' => ['size' => 9],
                    ]);

                    // تمييز أعمدة القيم المالية بلون أخضر خفيف
                    $sheet->getStyle("I12:{$lastCol}{$lastDataRow}")->applyFromArray([
                        'font' => ['bold' => true],
                    ]);

                    // إطار خارجي سميك للجدول
                    $sheet->getStyle("A11:{$lastCol}{$lastDataRow}")->applyFromArray([
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color'       => ['rgb' => '2C3E50'],
                            ],
                        ],
                    ]);
                }

                // ── تجميد الصفوف (العنوان ثابت عند التمرير) ─────
                $sheet->getDelegate()->freezePane('A12');

                // ── تعليق تذييلي ─────────────────────────────────
                $footerRow = $lastDataRow + 2;
                $sheet->setCellValue("A{$footerRow}", 'تم إنشاء هذا التقرير تلقائياً بواسطة منصة SafeDests — ' . date('Y-m-d H:i:s'));
                $sheet->mergeCells("A{$footerRow}:{$lastCol}{$footerRow}");
                $sheet->getStyle("A{$footerRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'font'      => ['italic' => true, 'color' => ['rgb' => '888888'], 'size' => 8],
                ]);
            },
        ];
    }
}
