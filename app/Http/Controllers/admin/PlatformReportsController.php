<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Task;
use App\Models\Teams;
use App\Models\User;
use App\Services\ReportService;
use App\Exports\CustomerTasksExport;
use App\Exports\DriverTasksExport;
use App\Exports\TeamTasksExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PlatformReportsController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('permission:view_reports', ['only' => ['index']]);
        $this->middleware('permission:generate_reports', ['only' => [
          'customerReport',
          'generateCustomerTasksReport',
          'exportToExcel',
          'exportToPdf',
          'getReportPreview',
          'driverReport',
          'generateDriverTasksReport',
          'exportDriverTasksToExcel',
          'exportDriverTasksToPdf',
          'getDriverTasksPreview',
          'teamReport',
          'generateTeamTasksReport',
          'exportTeamTasksToExcel',
          'exportTeamTasksToPdf',
          'getTeamTasksPreview'
        ]]);

    }


    public function index()
    {
        try {
            return view('admin.reports.index');
        } catch (\Exception $e) {
            return response('Error: ' . $e->getMessage(), 500);
        }
    }
    /**
    * View customer tasks report page
    */
    public function customerReport()
    {

        // Get required data for the view
        $customers = Customer::select('id', 'name', 'company_name')->get();
        $drivers = Driver::select('id', 'name', 'phone')->get();
        $teams = Teams::select('id', 'name')->get();

        $taskStatuses = [
            'in_progress' => 'in_progress',
            'advertised' => 'advertised',
            'assign' => 'assign',
            'started' => 'started',
            'in pickup point' => 'in pickup point',
            'loading' => 'loading',
            'in the way' => 'in the way',
            'in delivery point' => 'in delivery point',
            'unloading' => 'unloading',
            'invoiced' => 'invoiced',
            'completed' => 'completed',
            'canceled' => 'canceled',
            'refund' => 'refund'
        ];

        $paymentStatuses = [
             'waiting' => 'waiting',
            'completed' => 'completed',
            'pending' => 'pending'
        ];

        $paymentMethods = [
            'bank_transfer' => 'bank transfer',
            'credit_card' => 'credit card',
            'wallet' => 'wallet'
        ];

        return view('admin.reports.customer-tasks', compact(
            'customers',
            'drivers',
            'teams',
            'taskStatuses',
            'paymentStatuses',
            'paymentMethods'
        ));
    }

    /**
     * Generate customer tasks report data
     */
    public function generateCustomerTasksReport(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'customer_ids' => 'required|array|min:1',
                'customer_ids.*' => 'exists:customers,id',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'columns' => 'required|array|min:4',
                'export_type' => 'required|in:excel,pdf'
            ]);

            // Generate report data
            $reportData = $this->reportService->generateCustomerTasksReport($request->all());

            if ($request->export_type === 'excel') {
                return $this->exportToExcel($reportData, $request->all());
            } else {
                return $this->exportToPdf($reportData, $request->all());
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export report to Excel
     */
    private function exportToExcel($reportData, $filters)
    {
        $filename = 'customer_tasks_report_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new CustomerTasksExport($reportData, $filters),
            $filename
        );
    }

    /**
     * Export report to PDF (using browser print)
     */
    private function exportToPdf($reportData, $filters)
    {
        // Get customer names for the report
        $customerNames = Customer::whereIn('id', $filters['customer_ids'])
            ->get();

        return view('admin.reports.pdf.customer-tasks-simple', compact(
            'reportData',
            'filters',
            'customerNames'
        ));
    }

    /**
     * Get report preview data (for table display)
     */
    public function getReportPreview(Request $request)
    {
        try {
            $request->validate([
                'customer_ids' => 'required|array|min:1',
                'customer_ids.*' => 'exists:customers,id',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ]);

            $reportData = $this->reportService->generateCustomerTasksReport($request->all(), true); // Preview mode

            return response()->json([
                'success' => true,
                'data' => $reportData['tasks'],
                'summary' => $reportData['summary']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
       * View Driver tasks report page
       */
    public function driverReport()
    {
        $customers = Customer::select('id', 'name', 'company_name')->get();
        $drivers = Driver::with('team:id,name')->select('id', 'name', 'phone', 'team_id')->get();
        $teams = Teams::select('id', 'name')->get();

        $taskStatuses = [
             'in_progress' => 'in_progress',
             'advertised' => 'advertised',
             'assign' => 'assign',
             'started' => 'started',
             'in pickup point' => 'in pickup point',
             'loading' => 'loading',
             'in the way' => 'in the way',
             'in delivery point' => 'in delivery point',
             'unloading' => 'unloading',
             'invoiced' => 'invoiced',
             'completed' => 'completed',
             'canceled' => 'canceled',
             'refund' => 'refund'
         ];

        $paymentStatuses = [
            'pending' => __('Pending'),
            'completed' => __('Completed'),
            'waiting' => __('Waiting')
        ];

        $paymentMethods = [
            'cash' => 'cash',
            'bank_transfer' => 'bank transfer',
            'credit_card' => 'credit card',
            'wallet' => 'wallet'
        ];

        return view('admin.reports.driver-tasks', compact(
            'customers',
            'drivers',
            'teams',
            'taskStatuses',
            'paymentStatuses',
            'paymentMethods'
        ));
    }
    /**
     * Generate driver tasks report data
     */
    public function generateDriverTasksReport(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'driver_ids' => 'required|array|min:1',
                'driver_ids.*' => 'exists:drivers,id',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'columns' => 'required|array|min:4',
                'export_type' => 'required|in:excel,pdf'
            ]);

            // Generate report data
            $reportData = $this->reportService->generateDriverTasksReport($request->all());

            if ($request->export_type === 'excel') {
                return $this->exportDriverTasksToExcel($reportData, $request->all());
            } else {
                return $this->exportDriverTasksToPdf($reportData, $request->all());
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get driver tasks report preview
     */
    public function getDriverTasksPreview(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'driver_ids' => 'required|array|min:1',
                'driver_ids.*' => 'exists:drivers,id',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ]);

            $reportData = $this->reportService->generateDriverTasksReport($request->all(), true); // Preview mode

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $reportData['tasks'],
                    'summary' => $reportData['summary']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate team tasks report data
     */
    public function generateTeamTasksReport(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'team_ids' => 'required|array|min:1',
                'team_ids.*' => 'exists:teams,id',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'columns' => 'required|array|min:4',
                'export_type' => 'required|in:excel,pdf'
            ]);

            // Generate report data
            $reportData = $this->reportService->generateTeamTasksReport($request->all());

            if ($request->export_type === 'excel') {
                return $this->exportTeamTasksToExcel($reportData, $request->all());
            } else {
                return $this->exportTeamTasksToPdf($reportData, $request->all());
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء التقرير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team tasks report preview
     */
    public function getTeamTasksPreview(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'team_ids' => 'required|array|min:1',
                'team_ids.*' => 'exists:teams,id',
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ]);

            $reportData = $this->reportService->generateTeamTasksReport($request->all(), true); // Preview mode

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks' => $reportData['tasks'],
                    'summary' => $reportData['summary']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export driver tasks to Excel
     */
    private function exportDriverTasksToExcel($reportData, $filters)
    {

        // Use DriverTasksExport class like CustomerTasksExport
        return Excel::download(new DriverTasksExport($reportData, $filters), 'driver-tasks-report-' . date('Y-m-d-H-i-s') . '.xlsx');
    }

    /**
     * Export driver tasks to PDF
     */
    private function exportDriverTasksToPdf($reportData, $filters)
    {
        // Get driver names for the report
        $driverIds = $filters['driver_ids'] ?? [];
        $driverNames = \App\Models\Driver::whereIn('id', $driverIds)->get(['id', 'name', 'phone']);

        // Return view directly for browser printing (like customer tasks)
        return view('admin.reports.pdf.driver-tasks-simple', compact('reportData', 'filters', 'driverNames'));
    }

    /**
     * Export team tasks to Excel
     */
    private function exportTeamTasksToExcel($reportData, $filters)
    {
        // Use TeamTasksExport class like CustomerTasksExport
        return Excel::download(new TeamTasksExport($reportData, $filters), 'team-tasks-report-' . date('Y-m-d-H-i-s') . '.xlsx');
    }

    /**
     * Export team tasks to PDF
     */
    private function exportTeamTasksToPdf($reportData, $filters)
    {
        // Get team names for the report
        $teamIds = $filters['team_ids'] ?? [];
        $teamNames = \App\Models\Teams::whereIn('id', $teamIds)->get(['id', 'name']);

        // Return view directly for browser printing (like driver tasks)
        return view('admin.reports.pdf.team-tasks-simple', compact('reportData', 'filters', 'teamNames'));
    }
}
