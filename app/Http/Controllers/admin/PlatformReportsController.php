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
    }

    /**
     * Display the main reports page
     */
    public function index()
    {
        // Debug: Check if we reach this method
        \Log::info('PlatformReportsController@index called');

        // Temporarily disable permission check for testing
        // TODO: Re-enable after fixing permissions
        /*
        if (!Auth::user()->can('view_reports')) {
            abort(403, 'Unauthorized access to reports');
        }
        */

        try {
            return view('admin.reports.index');
        } catch (\Exception $e) {
            \Log::error('Error in reports index: ' . $e->getMessage());
            return response('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display customer tasks report page
     */
    public function customerTasks()
    {
        // Temporarily disable permission check for testing
        // TODO: Re-enable after fixing permissions
        /*
        if (!Auth::user()->can('view_reports')) {
            abort(403, 'Unauthorized access to reports');
        }
        */

        $user = Auth::user();

        // Get customers based on user permissions
        if ($user->can('mange_customers')) {
            $customers = Customer::where('status', '!=', 'deleted')->get();
        } else {
            $customers = $user->customers;
        }

        // Get drivers based on user permissions
        if ($user->can('mange_drivers')) {
            $drivers = Driver::where('status', 'active')->get();
        } else {
            $teamIds = $user->teams->pluck('id')->toArray();
            $drivers = Driver::whereIn('team_id', $teamIds)->where('status', 'active')->get();
        }

        // Get teams based on user permissions
        if ($user->can('mange_teams')) {
            $teams = Teams::all();
        } else {
            $teams = $user->teams;
        }

        // Task statuses
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
            'completed' => 'completed',
            'canceled' => 'canceled'
        ];

        // Payment statuses
        $paymentStatuses = [
            'waiting' => 'waiting',
            'completed' => 'completed',
            'pending' => 'pending'
        ];

        // Payment methods
        $paymentMethods = [
            'credit' => 'credit',
            'banking' => 'banking',
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
}
