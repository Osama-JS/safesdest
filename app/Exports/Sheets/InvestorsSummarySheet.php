<?php

namespace App\Exports\Sheets;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

class InvestorsSummarySheet implements FromCollection, WithHeadings, WithColumnWidths, WithTitle, WithEvents, WithCharts
{
    protected $fromDate;
    protected $toDate;
    protected $investorIds;
    protected $totalRecords = 0;
    protected $totals = [];

    protected $chartCategories = [];
    protected $chartInvestBalances = [];
    protected $chartInvestDeposits = [];
    protected $chartInvestFunded = [];
    protected $chartInvestReturns = [];
    protected $chartCommBalances = [];
    protected $chartCommEarned = [];
    protected $chartCommWithdrawn = [];

    public function __construct($fromDate = null, $toDate = null, $investorIds = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->investorIds = $investorIds ? (array)$investorIds : null;
    }

    public function collection()
    {
        $query = User::where('investor', true)->where('status', '!=', 'deleted');

        if (!empty($this->investorIds)) {
            $query->whereIn('id', $this->investorIds);
        }

        $investors = $query->with([
            'activeInvestmentContract',
            'investorWallet.transactions',
            'userWallet.transactions',
            'investorTasks'
        ])->get();

        $rows = [];
        $seq = 1;

        $sumInvestWalletBal = 0;
        $sumInvestDeposits = 0;
        $sumInvestFunded = 0;
        $sumInvestReturns = 0;
        $sumCommWalletBal = 0;
        $sumCommEarned = 0;
        $sumCommWithdrawn = 0;
        $sumTasksCount = 0;
        $sumTasksTotalAmount = 0;

        $this->chartCategories = [];
        $this->chartInvestBalances = [];
        $this->chartInvestDeposits = [];
        $this->chartInvestFunded = [];
        $this->chartInvestReturns = [];
        $this->chartCommBalances = [];
        $this->chartCommEarned = [];
        $this->chartCommWithdrawn = [];

        foreach ($investors as $inv) {
            $contract = $inv->activeInvestmentContract;
            $contractType = $contract 
                ? ($contract->contract_type == 'task_investment' ? 'استثمار بالمهام' : 'استثمار عام') 
                : 'بدون عقد';
            
            $commissionRate = '—';
            if ($contract) {
                $commissionRate = $contract->commission_type === 'percentage' 
                    ? ($contract->commission_value . '%') 
                    : ($contract->commission_value . ' ر.س');
            }

            $dedicatedCustomer = $contract && $contract->customer ? $contract->customer->name : 'الكل / غير محدد';

            // محفظة الاستثمار
            $investWallet = $inv->investorWallet;
            $investBalance = $investWallet ? (float)$investWallet->balance : 0.0;

            // حركات محفظة الاستثمار (مفلترة بالفترة إن وجدت)
            $investTransQuery = $investWallet ? $investWallet->transactions() : null;
            if ($investTransQuery && $this->fromDate && $this->toDate) {
                $investTransQuery->whereBetween('created_at', [
                    Carbon::parse($this->fromDate)->startOfDay(),
                    Carbon::parse($this->toDate)->endOfDay()
                ]);
            }
            $investTrans = $investTransQuery ? $investTransQuery->get() : collect([]);

            $investDeposits = (float)$investTrans->where('transaction_type', 'credit')
                ->whereIn('source_type', ['capital', 'deposit', 'hyperpay'])
                ->sum('amount');
            if ($investDeposits == 0 && $investTrans->where('transaction_type', 'credit')->count() > 0) {
                $investDeposits = (float)$investTrans->where('transaction_type', 'credit')
                    ->whereNotIn('source_type', ['capital_return', 'refund'])
                    ->sum('amount');
            }

            $investFunded = (float)$investTrans->where('transaction_type', 'debit')->sum('amount');
            $investReturns = (float)$investTrans->where('transaction_type', 'credit')
                ->whereIn('source_type', ['capital_return', 'refund'])
                ->sum('amount');

            // محفظة العمولات والأرباح
            $commWallet = $inv->userWallet;
            $commBalance = $commWallet ? (float)$commWallet->balance : 0.0;

            $commTransQuery = $commWallet ? $commWallet->transactions() : null;
            if ($commTransQuery && $this->fromDate && $this->toDate) {
                $commTransQuery->whereBetween('created_at', [
                    Carbon::parse($this->fromDate)->startOfDay(),
                    Carbon::parse($this->toDate)->endOfDay()
                ]);
            }
            $commTrans = $commTransQuery ? $commTransQuery->get() : collect([]);

            $commEarned = (float)$commTrans->where('transaction_type', 'credit')->sum('amount');
            $commWithdrawn = (float)$commTrans->where('transaction_type', 'debit')->sum('amount');

            // المهام المستثمرة
            $tasksQuery = $inv->investorTasks();
            if ($this->fromDate && $this->toDate) {
                $tasksQuery->whereBetween('created_at', [
                    Carbon::parse($this->fromDate)->startOfDay(),
                    Carbon::parse($this->toDate)->endOfDay()
                ]);
            }
            $tasksCount = (int)$tasksQuery->count();
            $tasksTotal = (float)$tasksQuery->sum('total_price');

            // التجميع للإجمالي
            $sumInvestWalletBal += $investBalance;
            $sumInvestDeposits += $investDeposits;
            $sumInvestFunded += $investFunded;
            $sumInvestReturns += $investReturns;
            $sumCommWalletBal += $commBalance;
            $sumCommEarned += $commEarned;
            $sumCommWithdrawn += $commWithdrawn;
            $sumTasksCount += $tasksCount;
            $sumTasksTotalAmount += $tasksTotal;

            // تخزين البيانات للرسوم البيانية
            $this->chartCategories[] = $inv->name;
            $this->chartInvestBalances[] = $investBalance;
            $this->chartInvestDeposits[] = $investDeposits;
            $this->chartInvestFunded[] = $investFunded;
            $this->chartInvestReturns[] = $investReturns;
            $this->chartCommBalances[] = $commBalance;
            $this->chartCommEarned[] = $commEarned;
            $this->chartCommWithdrawn[] = $commWithdrawn;

            $statusText = match ($inv->status) {
                'active' => 'نشط',
                'inactive' => 'غير نشط',
                'banned' => 'محظور',
                default => $inv->status
            };

            $rows[] = [
                'seq' => $seq++,
                'id' => '#' . $inv->id,
                'name' => $inv->name,
                'phone' => $inv->phone ?? '—',
                'email' => $inv->email ?? '—',
                'status' => $statusText,
                'contract_type' => $contractType,
                'commission_rate' => $commissionRate,
                'dedicated_customer' => $dedicatedCustomer,
                'invest_balance' => $investBalance,
                'invest_deposits' => $investDeposits,
                'invest_funded' => $investFunded,
                'invest_returns' => $investReturns,
                'comm_balance' => $commBalance,
                'comm_earned' => $commEarned,
                'comm_withdrawn' => $commWithdrawn,
                'tasks_count' => $tasksCount,
                'tasks_total' => $tasksTotal,
            ];
        }

        $this->totalRecords = count($rows);
        $this->totals = [
            'invest_balance' => $sumInvestWalletBal,
            'invest_deposits' => $sumInvestDeposits,
            'invest_funded' => $sumInvestFunded,
            'invest_returns' => $sumInvestReturns,
            'comm_balance' => $sumCommWalletBal,
            'comm_earned' => $sumCommEarned,
            'comm_withdrawn' => $sumCommWithdrawn,
            'tasks_count' => $sumTasksCount,
            'tasks_total' => $sumTasksTotalAmount,
        ];

        return collect($rows);
    }

    public function headings(): array
    {
        return [
            'م',
            'رقم المستثمر',
            'اسم المستثمر',
            'رقم الجوال',
            'البريد الإلكتروني',
            'الحالة',
            'نوع العقد',
            'نسبة العمولة',
            'العميل المخصص',
            'رصيد الاستثمار (ر.س)',
            'إيداعات رأس المال (ر.س)',
            'تمويل المهام (ر.س)',
            'استردادات رأس المال (ر.س)',
            'رصيد الأرباح (ر.س)',
            'إجمالي الأرباح المستلمة (ر.س)',
            'إجمالي الأرباح المسحوبة (ر.س)',
            'عدد المهام الممولة',
            'إجمالي قيمة المهام (ر.س)',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // م
            'B' => 14,  // رقم المستثمر
            'C' => 24,  // اسم المستثمر
            'D' => 16,  // الجوال
            'E' => 26,  // البريد
            'F' => 12,  // الحالة
            'G' => 18,  // نوع العقد
            'H' => 14,  // نسبة العمولة
            'I' => 22,  // العميل المخصص
            'J' => 22,  // رصيد الاستثمار
            'K' => 22,  // إيداعات رأس المال
            'L' => 20,  // تمويل المهام
            'M' => 24,  // استردادات رأس المال
            'N' => 20,  // رصيد الأرباح
            'O' => 24,  // إجمالي الأرباح
            'P' => 24,  // المسحوبة
            'Q' => 18,  // عدد المهام
            'R' => 22,  // إجمالي المهام
        ];
    }

    public function title(): string
    {
        return 'ملخص وإحصائيات المستثمرين';
    }

    /**
     * إنشاء 3 رسوم بيانية مالية وتحليلية ذات قيمة وفائدة عالية
     */
    public function charts()
    {
        $count = count($this->chartCategories);
        if ($count === 0) {
            return [];
        }

        $sheetTitle = $this->title();
        $dataStartRow = 10;
        $dataEndRow = $dataStartRow + $count - 1;
        $chartStartRow = $dataEndRow + 3;
        $chartEndRow = $chartStartRow + 18;
        $chart2StartRow = $chartEndRow + 2;
        $chart2EndRow = $chart2StartRow + 18;

        // -------------------------------------------------------------
        // المخطط 1: كفاءة وتشغيل رأس المال (إيداعات vs تمويل المهام vs مستردات)
        // -------------------------------------------------------------
        $capLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$K\$9", null, 1, ['إيداعات رأس المال (ر.س)']),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$L\$9", null, 1, ['تمويل المهام (ر.س)']),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$M\$9", null, 1, ['استردادات رأس المال (ر.س)']),
        ];

        $capCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$10:\$C\${dataEndRow}", null, $count, $this->chartCategories),
        ];

        $capValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$K\$10:\$K\${dataEndRow}", null, $count, $this->chartInvestDeposits),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$L\$10:\$L\${dataEndRow}", null, $count, $this->chartInvestFunded),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$M\$10:\$M\${dataEndRow}", null, $count, $this->chartInvestReturns),
        ];

        $capSeries = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($capValues) - 1),
            $capLabels,
            $capCategories,
            $capValues
        );
        $capSeries->setPlotDirection(DataSeries::DIRECTION_COL);

        $capPlotArea = new PlotArea(null, [$capSeries]);
        $capLegend = new Legend(Legend::POSITION_TOPRIGHT, null, false);
        $capTitle = new Title('مؤشر كفاءة وتشغيل رأس المال (الإيداعات مقابل التمويل والمستردات)');

        $chart1 = new Chart(
            'capital_deployment_chart',
            $capTitle,
            $capLegend,
            $capPlotArea,
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            null
        );
        $chart1->setTopLeftPosition("A{$chartStartRow}");
        $chart1->setBottomRightPosition("I{$chartEndRow}");

        // -------------------------------------------------------------
        // المخطط 2: تحليل أداء العوائد والأرباح (الأرباح المكتسبة vs المسحوبة vs الرصيد المتاح)
        // -------------------------------------------------------------
        $profitLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$O\$9", null, 1, ['إجمالي الأرباح المستلمة (ر.س)']),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$P\$9", null, 1, ['إجمالي الأرباح المسحوبة (ر.س)']),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$N\$9", null, 1, ['رصيد الأرباح المتاح (ر.س)']),
        ];

        $profitCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$10:\$C\${dataEndRow}", null, $count, $this->chartCategories),
        ];

        $profitValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$O\$10:\$O\${dataEndRow}", null, $count, $this->chartCommEarned),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$P\$10:\$P\${dataEndRow}", null, $count, $this->chartCommWithdrawn),
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$N\$10:\$N\${dataEndRow}", null, $count, $this->chartCommBalances),
        ];

        $profitSeries = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($profitValues) - 1),
            $profitLabels,
            $profitCategories,
            $profitValues
        );
        $profitSeries->setPlotDirection(DataSeries::DIRECTION_COL);

        $profitPlotArea = new PlotArea(null, [$profitSeries]);
        $profitLegend = new Legend(Legend::POSITION_TOPRIGHT, null, false);
        $profitTitle = new Title('تحليل العوائد والأرباح (الأرباح المكتسبة مقابل السحوبات والرصيد المتبقي)');

        $chart2 = new Chart(
            'profit_performance_chart',
            $profitTitle,
            $profitLegend,
            $profitPlotArea,
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            null
        );
        $chart2->setTopLeftPosition("J{$chartStartRow}");
        $chart2->setBottomRightPosition("R{$chartEndRow}");

        // -------------------------------------------------------------
        // المخطط 3: الحصص السوقية لحجم تمويل المهام بين المستثمرين (Pie / Donut Chart)
        // -------------------------------------------------------------
        $pieLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$L\$9", null, 1, ['حجم تمويل المهام (ر.س)']),
        ];

        $pieCategories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$C\$10:\$C\${dataEndRow}", null, $count, $this->chartCategories),
        ];

        $pieValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$L\$10:\$L\${dataEndRow}", null, $count, $this->chartInvestFunded),
        ];

        $pieSeries = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, count($pieValues) - 1),
            $pieLabels,
            $pieCategories,
            $pieValues
        );

        $piePlotArea = new PlotArea(null, [$pieSeries]);
        $pieLegend = new Legend(Legend::POSITION_RIGHT, null, false);
        $pieTitle = new Title('الحصة النسبية لمساهمة كل مستثمر في تغطية وتمويل شحنات المنصة (%)');

        $chart3 = new Chart(
            'investor_market_share_funding',
            $pieTitle,
            $pieLegend,
            $piePlotArea,
            true,
            DataSeries::EMPTY_AS_GAP,
            null,
            null
        );
        $chart3->setTopLeftPosition("E{$chart2StartRow}");
        $chart3->setBottomRightPosition("N{$chart2EndRow}");

        return [$chart1, $chart2, $chart3];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastCol = 'R';

                // إدراج ترويسة التقرير
                $sheet->insertNewRowBefore(1, 8);

                // الشعار إذا توفر
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

                // عنوان التقرير
                $sheet->setCellValue('C1', 'منصة وجهات آمنة للخدمات اللوجستية والنقل (SafeDests)');
                $sheet->mergeCells("C1:{$lastCol}1");

                $sheet->setCellValue('C2', 'التقرير المالي والإحصائي الشامل لكافة المستثمرين ومحافظ الاستثمار والعمولات');
                $sheet->mergeCells("C2:{$lastCol}2");

                $periodText = ($this->fromDate && $this->toDate)
                    ? "الفترة الزمنية: من {$this->fromDate} إلى {$this->toDate}"
                    : "الفترة الزمنية: كافة الفترات السابقة حتى تاريخه";

                $sheet->setCellValue('C3', $periodText);
                $sheet->mergeCells("C3:{$lastCol}3");

                $sheet->setCellValue('C4', 'تاريخ الاستخراج: ' . Carbon::now()->format('Y-m-d H:i') . ' | تم الاستخراج بواسطة: ' . (auth()->user()?->name ?? 'لوحة الإدارة'));
                $sheet->mergeCells("C4:{$lastCol}4");

                // تنسيق ترويسة العنوان
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

                // ترويسة الجدول (الصف 9)
                $headerRow = 9;
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(32);

                // تنسيق صفوف البيانات
                $dataStartRow = 10;
                $dataEndRow = $dataStartRow + $this->totalRecords - 1;

                if ($this->totalRecords > 0) {
                    $sheet->getStyle("A{$dataStartRow}:{$lastCol}{$dataEndRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    // تظليل صفوف متبادلة (Zebra Striping)
                    for ($r = $dataStartRow; $r <= $dataEndRow; $r++) {
                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']]
                            ]);
                        }
                        $sheet->getRowDimension($r)->setRowHeight(24);
                    }

                    // صف الإجمالي العام
                    $summaryRow = $dataEndRow + 1;
                    $sheet->setCellValue("A{$summaryRow}", 'الإجمالي العام لكافة المستثمرين');
                    $sheet->mergeCells("A{$summaryRow}:I{$summaryRow}");

                    $sheet->setCellValue("J{$summaryRow}", $this->totals['invest_balance'] ?? 0);
                    $sheet->setCellValue("K{$summaryRow}", $this->totals['invest_deposits'] ?? 0);
                    $sheet->setCellValue("L{$summaryRow}", $this->totals['invest_funded'] ?? 0);
                    $sheet->setCellValue("M{$summaryRow}", $this->totals['invest_returns'] ?? 0);
                    $sheet->setCellValue("N{$summaryRow}", $this->totals['comm_balance'] ?? 0);
                    $sheet->setCellValue("O{$summaryRow}", $this->totals['comm_earned'] ?? 0);
                    $sheet->setCellValue("P{$summaryRow}", $this->totals['comm_withdrawn'] ?? 0);
                    $sheet->setCellValue("Q{$summaryRow}", $this->totals['tasks_count'] ?? 0);
                    $sheet->setCellValue("R{$summaryRow}", $this->totals['tasks_total'] ?? 0);

                    // تنسيق أرقام المبالغ كـ Currency Formatting
                    $sheet->getStyle("J10:P{$summaryRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');

                    $sheet->getStyle("Q10:Q{$summaryRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getStyle("R10:R{$summaryRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');

                    $sheet->getStyle("A{$summaryRow}:{$lastCol}{$summaryRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '94A3B8']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($summaryRow)->setRowHeight(28);
                }
            }
        ];
    }
}
