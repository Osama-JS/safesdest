<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\HyperpayPayout;
use App\Models\WithdrawalRequest;
use App\Models\Driver;
use App\Services\HyperPayPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PayoutRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_payout_requests', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:approve_payout_requests', ['only' => ['approve', 'reject']]);
    }

    /**
     * Display the Payout Requests listing and metrics.
     */
    public function index()
    {
        $metrics = [
            'pending_approval_count'  => HyperpayPayout::where('status', 'pending_approval')->count(),
            'pending_approval_amount' => HyperpayPayout::where('status', 'pending_approval')->sum('amount'),
            'processing_count'        => HyperpayPayout::whereIn('status', ['pending', 'processing'])->count(),
            'processing_amount'       => HyperpayPayout::whereIn('status', ['pending', 'processing'])->sum('amount'),
            'completed_count'         => HyperpayPayout::where('status', 'completed')->count(),
            'completed_amount'        => HyperpayPayout::where('status', 'completed')->sum('amount'),
            'rejected_count'          => HyperpayPayout::where('status', 'rejected')->count(),
            'failed_count'            => HyperpayPayout::where('status', 'failed')->count(),
        ];

        return view('admin.wallets.payout_requests.index', compact('metrics'));
    }

    /**
     * Get DataTables JSON data for Payout Requests.
     */
    public function getData(Request $request)
    {
        $query = HyperpayPayout::with(['driver', 'wallet', 'creator', 'approver', 'rejector', 'withdrawal'])
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'processing') {
                $query->whereIn('status', ['pending', 'processing']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('payout_type')) {
            $query->where('payout_type', $request->payout_type);
        }

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $totalRecords = (clone $query)->count();

        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('reference_id', 'like', "%{$search}%")
                  ->orWhere('payout_id', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhereHas('driver', function ($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('iban_number', 'like', "%{$search}%");
                  });
            });
        }

        $filteredRecords = (clone $query)->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $payouts = $query->skip($start)->take($length)->get();

        $canApprove = auth()->user()->can('approve_payout_requests');

        $data = $payouts->map(function ($payout) use ($canApprove) {
            $details = $payout->transaction_details ?? [];
            $beneficiaryName = $details['beneficiary_name'] ?? ($payout->driver?->beneficiary_name ?? '—');
            $iban = $details['iban'] ?? ($payout->driver?->iban_number ?? '—');
            $bankName = $details['bank_name'] ?? ($payout->driver?->bank_name ?? '—');

            $driverHtml = '—';
            if ($payout->driver) {
                $driverName = e($payout->driver->name);
                $driverMobile = e($payout->driver->mobile_number ?? $payout->driver->phone ?? '—');
                $driverUrl = route('drivers.show', [
                    'id'   => $payout->driver->id,
                    'name' => $payout->driver->name ?: 'driver'
                ]);
                $driverHtml = "<div><a href='{$driverUrl}' class='fw-bold text-primary'>{$driverName}</a><br><small class='text-muted' dir='ltr'>{$driverMobile}</small></div>";
            }

            $creatorName = $payout->creator ? e($payout->creator->name) : '—';
            $approverInfo = '—';
            if ($payout->status === 'completed' || $payout->status === 'processing') {
                if ($payout->approver) {
                    $approverInfo = e($payout->approver->name) . '<br><small class="text-muted">' . ($payout->approved_at ? $payout->approved_at->format('Y-m-d H:i') : '') . '</small>';
                }
            } elseif ($payout->status === 'rejected') {
                if ($payout->rejector) {
                    $approverInfo = '<span class="text-danger">' . e($payout->rejector->name) . '</span><br><small class="text-muted">' . ($payout->rejected_at ? $payout->rejected_at->format('Y-m-d H:i') : '') . '</small>';
                }
            }

            // Actions HTML
            $actions = '<div class="d-flex align-items-center gap-1">';
            
            // View details button
            $actions .= '<button type="button" class="btn btn-sm btn-icon btn-label-info btn-view-payout" data-id="' . $payout->id . '" title="' . __('عرض التفاصيل') . '"><i class="ti ti-eye"></i></button>';

            // Approve and Reject buttons (Only if pending_approval and user has permission)
            if ($payout->status === 'pending_approval' && $canApprove) {
                $actions .= '<button type="button" class="btn btn-sm btn-icon btn-label-success btn-approve-payout" data-id="' . $payout->id . '" data-ref="' . e($payout->reference_id) . '" data-amount="' . number_format($payout->amount, 2) . '" data-beneficiary="' . e($beneficiaryName) . '" title="' . __('مصادقة وتحويل') . '"><i class="ti ti-check"></i></button>';
                $actions .= '<button type="button" class="btn btn-sm btn-icon btn-label-danger btn-reject-payout" data-id="' . $payout->id . '" data-ref="' . e($payout->reference_id) . '" title="' . __('رفض الطلب') . '"><i class="ti ti-x"></i></button>';
            }

            $actions .= '</div>';

            return [
                'id'             => $payout->id,
                'reference_id'   => '<span class="badge bg-label-dark fw-bold">' . e($payout->reference_id) . '</span>',
                'created_at'     => $payout->created_at ? $payout->created_at->format('Y-m-d H:i') : '—',
                'driver'         => $driverHtml,
                'payout_type'    => '<span class="badge bg-label-secondary">' . $payout->payout_type_name . '</span>',
                'amount'         => '<span class="fw-bold text-success fs-6">' . number_format($payout->amount, 2) . '</span> <small class="text-muted">' . __('SAR') . '</small>',
                'beneficiary'    => '<div><strong>' . e($beneficiaryName) . '</strong><br><small class="text-muted" dir="ltr">' . e($iban) . '</small><br><span class="badge bg-label-info py-0">' . e($bankName) . '</span></div>',
                'created_by'     => $creatorName,
                'status'         => $payout->status_badge,
                'approver_info'  => $approverInfo,
                'actions'        => $actions,
            ];
        });

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Show detailed information of a payout request for modal.
     */
    public function show($id)
    {
        $payout = HyperpayPayout::with(['driver', 'wallet', 'creator', 'approver', 'rejector', 'withdrawal'])
            ->findOrFail($id);

        $details = $payout->transaction_details ?? [];

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $payout->id,
                'reference_id'     => $payout->reference_id,
                'payout_id'        => $payout->payout_id ?? '—',
                'bulk_id'          => $payout->bulk_id ?? '—',
                'amount'           => number_format($payout->amount, 2),
                'payout_type'      => $payout->payout_type,
                'payout_type_name' => $payout->payout_type_name,
                'status'           => $payout->status,
                'status_badge'     => $payout->status_badge,
                'created_at'       => $payout->created_at ? $payout->created_at->format('Y-m-d H:i:s') : '—',
                'created_by'       => $payout->creator?->name ?? '—',
                'approved_at'      => $payout->approved_at ? $payout->approved_at->format('Y-m-d H:i:s') : '—',
                'approved_by'      => $payout->approver?->name ?? '—',
                'rejected_at'      => $payout->rejected_at ? $payout->rejected_at->format('Y-m-d H:i:s') : '—',
                'rejected_by'      => $payout->rejector?->name ?? '—',
                'rejection_reason' => $payout->rejection_reason ?? '—',
                'failure_reason'   => $payout->failure_reason ?? '—',
                'driver'           => [
                    'id'            => $payout->driver?->id,
                    'name'          => $payout->driver?->name ?? '—',
                    'mobile'        => $payout->driver?->mobile_number ?? $payout->driver?->phone ?? '—',
                ],
                'bank_details'     => [
                    'beneficiary_name' => $details['beneficiary_name'] ?? ($payout->driver?->beneficiary_name ?? '—'),
                    'iban'             => $details['iban'] ?? ($payout->driver?->iban_number ?? '—'),
                    'bic'              => $details['bic'] ?? ($payout->driver?->bic_code ?? '—'),
                    'bank_name'        => $details['bank_name'] ?? ($payout->driver?->bank_name ?? '—'),
                    'country'          => $details['country'] ?? ($payout->driver?->bank_country ?? 'SA'),
                    'city'             => $details['city'] ?? ($payout->driver?->bank_city ?? 'Riyadh'),
                    'address'          => $details['address1'] ?? ($payout->driver?->bank_address1 ?? '—'),
                ],
                'notes'            => $details['description'] ?? ($details['admin_notes'] ?? '—'),
                'image_url'        => !empty($details['image']) ? asset('storage/' . $details['image']) : (!empty($details['receipt_image']) ? asset('storage/' . $details['receipt_image']) : null),
                'details'          => $details,
            ]
        ]);
    }

    /**
     * Approve and execute payout request to HyperPay API.
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // 1. Verify Manager Password
        if (!Hash::check($request->password, auth()->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => __('كلمة المرور الخاصة بك غير صحيحة.')
            ], 422);
        }

        $payout = HyperpayPayout::with(['driver', 'wallet', 'withdrawal'])->findOrFail($id);

        if ($payout->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => __('هذا الطلب ليس بحالة بانتظار المصادقة.')
            ], 422);
        }

        $driver = $payout->driver;
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => __('تعذر العثور على بيانات السائق المرتبط بهذا الطلب.')
            ], 422);
        }

        $details = $payout->transaction_details ?? [];
        $beneficiaryName = $details['beneficiary_name'] ?? HyperPayPayoutService::formatBeneficiaryName($driver->beneficiary_name);
        $iban = str_replace(' ', '', $details['iban'] ?? $driver->iban_number);
        $bic = $details['bic'] ?? $driver->bic_code;
        $country = $details['country'] ?? 'SA';
        $city = $details['city'] ?? ($driver->bank_city ?? 'Riyadh');
        $address1 = $details['address1'] ?? ($driver->bank_address1 ?? ($driver->address ?: 'Riyadh'));
        $address2 = $details['address2'] ?? '.';

        if (!$iban || !$bic || !$beneficiaryName) {
            return response()->json([
                'success' => false,
                'message' => __('بيانات التحويل البنكية غير مكتملة (اسم المستفيد أو الآيبان أو كود BIC).')
            ], 422);
        }

        // 2. Dispatch to HyperPay Payout API
        $payoutService = app(HyperPayPayoutService::class);
        $payoutResponse = $payoutService->sendPayout([
            'amount'           => $payout->amount,
            'currency'         => 'SAR',
            'externalId'       => $payout->reference_id,
            'beneficiary_name' => $beneficiaryName,
            'address1'         => $address1,
            'address2'         => $address2,
            'city'             => $city,
            'country'          => $country,
            'iban'             => $iban,
            'bic'              => $bic,
            'purpose'          => 'BA',
            'description'      => "Payout " . substr($beneficiaryName, 0, 20)
        ]);

        if (!$payoutResponse['status']) {
            $payout->update([
                'failure_reason' => $payoutResponse['message']
            ]);

            return response()->json([
                'success' => false,
                'message' => __('فشل إرسال التحويل إلى بوابة HyperPay: ') . $payoutResponse['message']
            ], 422);
        }

        $payoutId = $payoutResponse['data']['payoutId'] ?? 'N/A';
        $bulkId = $payoutResponse['data']['bulkId'] ?? 'N/A';

        // 3. Update Payout record to 'processing'
        $payout->update([
            'status'      => 'processing',
            'payout_id'   => $payoutId,
            'bulk_id'     => $bulkId,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // 4. Update related models if applicable
        if ($payout->payout_type === 'WD' && $payout->withdrawal) {
            $payout->withdrawal->update([
                'admin_notes' => ($payout->withdrawal->admin_notes ? $payout->withdrawal->admin_notes . ' | ' : '') . "تمت مصادقة التحويل وإرساله إلى HyperPay بواسطة " . auth()->user()->name
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('تمت المصادقة على طلب الدفع وإرسال التحويل المالي إلى هايبر باي بنجاح! العملية الآن قيد المعالجة البنكية.'),
            'data'    => [
                'payout_id' => $payoutId,
                'bulk_id'   => $bulkId,
            ]
        ]);
    }

    /**
     * Reject a payout request.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $payout = HyperpayPayout::with(['withdrawal'])->findOrFail($id);

        if ($payout->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => __('هذا الطلب ليس بحالة بانتظار المصادقة.')
            ], 422);
        }

        $payout->update([
            'status'           => 'rejected',
            'rejected_by'      => auth()->id(),
            'rejected_at'      => now(),
            'rejection_reason' => $request->reason,
        ]);

        // If it was linked to a withdrawal request, update withdrawal request
        if ($payout->payout_type === 'WD' && $payout->withdrawal) {
            $payout->withdrawal->update([
                'status'      => 'rejected',
                'admin_notes' => ($payout->withdrawal->admin_notes ? $payout->withdrawal->admin_notes . ' | ' : '') . "تم رفض طلب الـ Payout بواسطة المدير: " . $request->reason
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('تم رفض طلب الدفع عبر الـ Payout بنجاح.'),
        ]);
    }
}
