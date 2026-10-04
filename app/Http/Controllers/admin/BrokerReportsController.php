<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BrokerReportService;
use App\Exports\BrokerCommissionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class BrokerReportsController extends Controller
{
    protected $brokerReportService;

    public function __construct(BrokerReportService $brokerReportService)
    {
        $this->brokerReportService = $brokerReportService;
    }

    /**
     * Display Brokers Report Page
     */
    public function index()
    {
        $brokers = $this->brokerReportService->getBrokersList();

        return view('admin.reports.broker-commissions', compact('brokers'));
    }

    /**
     * Preview Report Data via AJAX
     */
    public function preview(Request $request)
    {
        try {
            $brokerIds = $request->input('broker_ids', []);
            if (is_array($brokerIds) && in_array('all', $brokerIds)) {
                $brokerIds = $this->brokerReportService->getBrokersList()->pluck('id')->toArray();
                $request->merge(['broker_ids' => $brokerIds]);
            }

            if (empty($brokerIds)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Please select at least one broker or click Select All to preview report')
                ], 422);
            }

            $request->validate([
                'report_mode' => 'nullable|in:transactions,aggregated',
                'broker_ids' => 'required|array|min:1',
                'broker_ids.*' => 'exists:users,id',
                'commission_sources' => 'nullable|array',
                'commission_sources.*' => 'in:all,customer,driver,task,investor',
                'commission_source' => 'nullable',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
            ]);

            $mode = $request->input('report_mode', 'transactions');

            if ($mode === 'aggregated') {
                $reportData = $this->brokerReportService->generateAggregatedBrokersReport($request->all());
                return response()->json([
                    'success' => true,
                    'mode' => 'aggregated',
                    'data' => $reportData['brokers'],
                    'summary' => $reportData['summary'],
                ]);
            } else {
                $reportData = $this->brokerReportService->generateWalletTransactionsReport($request->all(), true);
                return response()->json([
                    'success' => true,
                    'mode' => 'transactions',
                    'data' => $reportData['transactions'],
                    'summary' => $reportData['summary'],
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while fetching report data: :error', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    /**
     * Generate & Export Report (Excel or PDF)
     */
    public function generate(Request $request)
    {
        try {
            $brokerIds = $request->input('broker_ids', []);
            if (is_array($brokerIds) && in_array('all', $brokerIds)) {
                $brokerIds = $this->brokerReportService->getBrokersList()->pluck('id')->toArray();
                $request->merge(['broker_ids' => $brokerIds]);
            }

            if (empty($brokerIds)) {
                return back()->with('error', __('Please select at least one broker or click Select All to export report'));
            }

            $request->validate([
                'report_mode' => 'nullable|in:transactions,aggregated',
                'broker_ids' => 'required|array|min:1',
                'broker_ids.*' => 'exists:users,id',
                'commission_sources' => 'nullable|array',
                'commission_sources.*' => 'in:all,customer,driver,task',
                'commission_source' => 'nullable',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'export_type' => 'required|in:excel,pdf',
                'columns' => 'nullable|array',
            ]);

            $mode = $request->input('report_mode', 'transactions');

            if ($mode === 'aggregated') {
                $reportData = $this->brokerReportService->generateAggregatedBrokersReport($request->all());
            } else {
                $reportData = $this->brokerReportService->generateWalletTransactionsReport($request->all(), false);
            }

            if ($request->export_type === 'excel') {
                $filename = 'broker_commissions_report_' . $mode . '_' . date('Y-m-d_H-i-s') . '.xlsx';
                return Excel::download(new BrokerCommissionsExport($reportData, $request->all()), $filename);
            } else {
                return view('admin.reports.pdf.broker-report-simple', compact('reportData'));
            }
        } catch (Exception $e) {
            return back()->with('error', __('An error occurred while exporting report: :error', ['error' => $e->getMessage()]));
        }
    }
}
