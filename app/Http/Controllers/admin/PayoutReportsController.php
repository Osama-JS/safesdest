<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\HyperpayPayout;
use App\Models\Driver;
use App\Models\Teams;
use App\Models\User;
use App\Models\Bank;
use App\Exports\PayoutsReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Exception;

class PayoutReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_reports');
    }

    /**
     * Display the Payout Reports Main Page.
     */
    public function index()
    {
        $drivers = Driver::select('id', 'name', 'phone', 'driver_code')->orderBy('name')->get();
        $teams = Teams::select('id', 'name')->orderBy('name')->get();
        $users = User::select('id', 'name', 'email')->orderBy('name')->get();
        $banks = Bank::where('is_active', true)->orderBy('name')->get();

        $statuses = [
            'completed'        => __('ناجحة ومكتملة (Completed)'),
            'failed'           => __('فاشلة (Failed)'),
            'rejected'         => __('مرفوضة (Rejected)'),
            'pending_approval' => __('بانتظار الموافقة (Pending Approval)'),
            'processing'       => __('قيد المعالجة (Processing)'),
        ];

        return view('admin.reports.payouts.index', compact(
            'drivers',
            'teams',
            'users',
            'banks',
            'statuses'
        ));
    }

    /**
     * Build the filtered query based on input parameters.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $query = HyperpayPayout::with(['driver', 'team', 'user', 'creator', 'approver', 'rejector'])
            ->orderBy('created_at', 'desc');

        // Date Range Filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        // Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'processing') {
                $query->whereIn('status', ['pending', 'processing']);
            } elseif ($request->status === 'failed_or_rejected') {
                $query->whereIn('status', ['failed', 'rejected']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Payout Section / Beneficiary Type Filter
        if ($request->filled('entity_type') && $request->entity_type !== 'all') {
            switch ($request->entity_type) {
                case 'driver':
                    $query->where(function ($q) {
                        $q->whereNotNull('driver_id')
                          ->orWhereIn('payout_type', ['WD', 'MT', 'WP']);
                    });
                    if ($request->filled('entity_id')) {
                        $query->where('driver_id', $request->entity_id);
                    }
                    break;
                case 'team':
                    $query->where(function ($q) {
                        $q->whereNotNull('team_id')
                          ->orWhereIn('payout_type', ['TPW', 'TWM', 'TWP']);
                    });
                    if ($request->filled('entity_id')) {
                        $query->where('team_id', $request->entity_id);
                    }
                    break;
                case 'investor':
                    $query->where(function ($q) {
                        $q->whereIn('payout_type', ['IPW', 'IWD', 'INV'])
                          ->orWhereHas('user', function ($uq) {
                              $uq->where('type', 'investor');
                          });
                    });
                    if ($request->filled('entity_id')) {
                        $query->where('user_id', $request->entity_id);
                    }
                    break;
                case 'user':
                    $query->where(function ($q) {
                        $q->whereNotNull('user_id')
                          ->orWhere('payout_type', 'UWP');
                    })->whereNotIn('payout_type', ['IPW', 'IWD', 'INV']);
                    if ($request->filled('entity_id')) {
                        $query->where('user_id', $request->entity_id);
                    }
                    break;
            }
        } elseif ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        } elseif ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Bank / BIC Filter
        if ($request->filled('bic_code') && $request->bic_code !== 'all') {
            $bic = $request->bic_code;
            $query->where(function ($q) use ($bic) {
                $q->where('transaction_details->bic', $bic)
                  ->orWhere('webhook_payload->hyperpayDetails->beneficiary->bicCode', $bic)
                  ->orWhereHas('driver', function ($dq) use ($bic) {
                      $dq->where('bic_code', $bic);
                  });
            });
        }

        // Amount Range Filter
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', (float) $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', (float) $request->max_amount);
        }

        // Search Query (Reference, Payout ID, Beneficiary, IBAN)
        if ($request->filled('search_query')) {
            $search = trim($request->search_query);
            $query->where(function ($q) use ($search) {
                $q->where('reference_id', 'like', "%{$search}%")
                  ->orWhere('payout_id', 'like', "%{$search}%")
                  ->orWhere('bulk_id', 'like', "%{$search}%")
                  ->orWhere('transaction_details->beneficiary_name', 'like', "%{$search}%")
                  ->orWhere('transaction_details->iban', 'like', "%{$search}%")
                  ->orWhere('webhook_payload->hyperpayDetails->beneficiary->fullName', 'like', "%{$search}%")
                  ->orWhere('webhook_payload->hyperpayDetails->beneficiary->iban', 'like', "%{$search}%")
                  ->orWhereHas('driver', function ($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('iban_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('team', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return $query;
    }

    /**
     * Compute summary KPIs for the report.
     */
    protected function computeSummary($query)
    {
        $base = clone $query;

        $totalCount  = (clone $base)->count();
        $totalAmount = (clone $base)->sum('amount');

        $completedCount  = (clone $base)->where('status', 'completed')->count();
        $completedAmount = (clone $base)->where('status', 'completed')->sum('amount');

        $failedCount  = (clone $base)->where('status', 'failed')->count();
        $failedAmount = (clone $base)->where('status', 'failed')->sum('amount');

        $rejectedCount  = (clone $base)->where('status', 'rejected')->count();
        $rejectedAmount = (clone $base)->where('status', 'rejected')->sum('amount');

        $pendingCount  = (clone $base)->whereIn('status', ['pending', 'processing', 'pending_approval'])->count();
        $pendingAmount = (clone $base)->whereIn('status', ['pending', 'processing', 'pending_approval'])->sum('amount');

        return [
            'total_count'       => $totalCount,
            'total_amount'      => round((float) $totalAmount, 2),
            'completed_count'   => $completedCount,
            'completed_amount'  => round((float) $completedAmount, 2),
            'failed_count'      => $failedCount,
            'failed_amount'     => round((float) $failedAmount, 2),
            'rejected_count'    => $rejectedCount,
            'rejected_amount'   => round((float) $rejectedAmount, 2),
            'pending_count'     => $pendingCount,
            'pending_amount'    => round((float) $pendingAmount, 2),
        ];
    }

    /**
     * Format a single payout record into an array.
     */
    public static function formatPayoutRecord(HyperpayPayout $payout)
    {
        $details = $payout->transaction_details ?? [];
        $hyperpayBeneficiary = $payout->webhook_payload['hyperpayDetails']['beneficiary'] ?? [];

        // Determine Entity Type and Name
        $entityType = 'أخرى';
        $entityName = '—';
        $entityUrl  = null;

        if ($payout->driver) {
            $entityType = 'سائق';
            $entityName = $payout->driver->name;
            $slug = urlencode($payout->driver->name ?? 'driver');
            $entityUrl = url("admin/drivers/account/{$payout->driver->id}/{$slug}");
        } elseif ($payout->team) {
            $entityType = 'فريق';
            $entityName = $payout->team->name;
            $entityUrl = url("admin/teams/wallets/{$payout->team->id}");
        } elseif ($payout->user) {
            $entityType = 'مستخدم / مستثمر';
            $entityName = $payout->user->name;
            $entityUrl = url("admin/users/{$payout->user->id}");
        }

        // Beneficiary Name
        $beneficiaryName = $details['beneficiary_name']
            ?? $hyperpayBeneficiary['fullName']
            ?? $payout->driver?->beneficiary_name
            ?? $payout->driver?->name
            ?? $payout->team?->beneficiary_name
            ?? $payout->team?->name
            ?? $payout->user?->name
            ?? '—';

        // IBAN
        $iban = $details['iban']
            ?? $hyperpayBeneficiary['iban']
            ?? $payout->driver?->iban_number
            ?? $payout->team?->iban_number
            ?? $payout->user?->iban_number
            ?? '—';

        // Bank / BIC
        $bankName = $details['bank_name']
            ?? $details['bic']
            ?? $hyperpayBeneficiary['bicCode']
            ?? $payout->driver?->bank_name
            ?? $payout->driver?->bic_code
            ?? $payout->team?->bank_name
            ?? '—';

        // Status Label & Badge
        $status = $payout->status;
        $statusBadge = 'bg-label-secondary';
        $statusLabel = $status;

        switch ($status) {
            case 'completed':
                $statusBadge = 'bg-label-success';
                $statusLabel = __('ناجحة (Completed)');
                break;
            case 'failed':
                $statusBadge = 'bg-label-danger';
                $statusLabel = __('فاشلة (Failed)');
                break;
            case 'rejected':
                $statusBadge = 'bg-label-dark';
                $statusLabel = __('مرفوضة (Rejected)');
                break;
            case 'pending_approval':
                $statusBadge = 'bg-label-warning';
                $statusLabel = __('بانتظار الموافقة');
                break;
            case 'pending':
            case 'processing':
                $statusBadge = 'bg-label-info';
                $statusLabel = __('قيد المعالجة');
                break;
        }

        // Payout Section & Badge
        $payoutSection = __('Payout سائقين');
        $sectionBadge  = 'bg-label-info';
        if ($payout->team_id || in_array($payout->payout_type, ['TPW', 'TWM', 'TWP'])) {
            $payoutSection = __('Payout فرق');
            $sectionBadge  = 'bg-label-primary';
        } elseif (in_array($payout->payout_type, ['IPW', 'IWD', 'INV']) || ($payout->user && $payout->user->type === 'investor')) {
            $payoutSection = __('Payout مستثمرين');
            $sectionBadge  = 'bg-label-warning';
        } elseif ($payout->user_id || $payout->payout_type === 'UWP') {
            $payoutSection = __('Payout مستخدمين');
            $sectionBadge  = 'bg-label-secondary';
        }

        // Payout Operation Description
        $payoutTypeLabel = match ($payout->payout_type) {
            'WD'  => __('طلب سحب رصيد سائق'),
            'MT'  => __('تحويل Payout مباشر لسائق'),
            'WP'  => __('تسوية رصيد سائق'),
            'TPW' => __('طلب سحب رصيد فريق'),
            'TWM' => __('تسوية رصيد فريق'),
            'TWP' => __('صرف مستحقات فريق'),
            'UWP' => __('صرف مستحقات مستخدم'),
            'IPW' => __('صرف أرباح مستثمر'),
            'IWD' => __('طلب سحب أرباح مستثمر'),
            default => 'Payout ' . ($payout->payout_type ?? ''),
        };

        // Failure / Rejection Reason
        $reason = $payout->failure_reason
            ?? $payout->rejection_reason
            ?? $payout->webhook_payload['responseMessage']
            ?? $payout->webhook_payload['hyperpayDetails']['responseMessage']
            ?? null;

        return [
            'id'                  => $payout->id,
            'reference_id'        => $payout->reference_id ?? '—',
            'payout_id'           => $payout->payout_id ?? '—',
            'payout_type'         => $payout->payout_type,
            'payout_type_label'   => $payoutTypeLabel,
            'payout_section'      => $payoutSection,
            'section_badge'       => $sectionBadge,
            'entity_type'         => $entityType,
            'entity_name'         => $entityName,
            'entity_url'          => $entityUrl,
            'beneficiary_name'    => $beneficiaryName,
            'iban'                => $iban,
            'bank_name'           => $bankName,
            'amount'              => (float) $payout->amount,
            'amount_formatted'    => number_format((float) $payout->amount, 2) . ' ' . __('SAR'),
            'status'              => $status,
            'status_label'        => $statusLabel,
            'status_badge'        => $statusBadge,
            'reason'              => $reason,
            'created_at'          => $payout->created_at ? $payout->created_at->format('Y-m-d H:i') : '—',
            'created_at_human'    => $payout->created_at ? $payout->created_at->diffForHumans() : '—',
            'creator_name'        => $payout->creator?->name ?? '—',
            'approver_name'       => $payout->approver?->name ?? '—',
        ];
    }

    /**
     * Preview report data via AJAX.
     */
    public function preview(Request $request)
    {
        try {
            $query = $this->buildFilteredQuery($request);
            $summary = $this->computeSummary($query);

            // Fetch records for preview (limit to 250 for speed in preview table)
            $payouts = (clone $query)->limit(250)->get()->map(function ($payout) {
                return self::formatPayoutRecord($payout);
            });

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'data'    => $payouts,
                'count'   => $payouts->count(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('حدث خطأ أثناء تحميل بيانات التقرير: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * Generate & Export Report (Excel or PDF).
     */
    public function generate(Request $request)
    {
        try {
            $request->validate([
                'export_type' => 'required|in:excel,pdf',
                'columns'     => 'nullable|array',
            ]);

            $query = $this->buildFilteredQuery($request);
            $summary = $this->computeSummary($query);

            $payouts = $query->get()->map(function ($payout) {
                return self::formatPayoutRecord($payout);
            });

            $reportData = [
                'summary'  => $summary,
                'payouts'  => $payouts,
                'filters'  => $request->all(),
                'date'     => now()->format('Y-m-d H:i:s'),
            ];

            if ($request->export_type === 'excel') {
                $filename = 'payouts_report_' . date('Y-m-d_H-i-s') . '.xlsx';
                return Excel::download(new PayoutsReportExport($reportData, $request->all()), $filename);
            } else {
                return view('admin.reports.pdf.payouts-report-simple', compact('reportData'));
            }
        } catch (Exception $e) {
            return back()->with('error', __('حدث خطأ أثناء تصدير التقرير: :error', ['error' => $e->getMessage()]));
        }
    }
}
