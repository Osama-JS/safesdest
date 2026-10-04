<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\HyperpayPayout;
use App\Models\User;
use App\Models\UserWallet;
use App\Models\InvestorCommissionWithdrawal;
use App\Services\HyperPayPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class InvestorPayoutRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_payout_requests|view_investors', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:approve_payout_requests|save_investors', ['only' => ['approve', 'reject']]);
    }

    /**
     * Base query for investor payouts
     */
    protected function baseInvestorPayoutQuery()
    {
        return HyperpayPayout::where(function ($q) {
            $q->whereNotNull('user_id')
              ->orWhereNotNull('user_wallet_id')
              ->orWhereNotNull('source_commission_withdrawal_id')
              ->orWhereIn('payout_type', ['IPW', 'IWD', 'UWP']);
        });
    }

    /**
     * Display the Investor Payout Requests listing and metrics.
     */
    public function index()
    {
        $metrics = [
            'pending_approval_count'  => $this->baseInvestorPayoutQuery()->where('status', 'pending_approval')->count(),
            'pending_approval_amount' => $this->baseInvestorPayoutQuery()->where('status', 'pending_approval')->sum('amount'),
            'processing_count'        => $this->baseInvestorPayoutQuery()->whereIn('status', ['pending', 'processing'])->count(),
            'processing_amount'       => $this->baseInvestorPayoutQuery()->whereIn('status', ['pending', 'processing'])->sum('amount'),
            'completed_count'         => $this->baseInvestorPayoutQuery()->where('status', 'completed')->count(),
            'completed_amount'        => $this->baseInvestorPayoutQuery()->where('status', 'completed')->sum('amount'),
            'rejected_count'          => $this->baseInvestorPayoutQuery()->where('status', 'rejected')->count(),
            'failed_count'            => $this->baseInvestorPayoutQuery()->where('status', 'failed')->count(),
        ];

        // Fetch investors who have commission wallets or have investor role
        $investors = User::whereHas('userWallet')
            ->orWhereHas('investorWallet')
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->get();

        return view('admin.investors.payout_requests.index', compact('metrics', 'investors'));
    }

    /**
     * Get DataTables JSON data for Investor Payout Requests.
     */
    public function getData(Request $request)
    {
        $query = $this->baseInvestorPayoutQuery()
            ->with(['user', 'userWallet', 'creator', 'approver', 'rejector', 'commissionWithdrawal'])
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'processing') {
                $query->whereIn('status', ['pending', 'processing']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('investor_id')) {
            $query->where('user_id', $request->investor_id);
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
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('iban_number', 'like', "%{$search}%")
                         ->orWhere('beneficiary_name', 'like', "%{$search}%");
                  });
            });
        }

        $filteredRecords = (clone $query)->count();

        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $payouts = $query->skip($start)->take($length)->get();

        $canApprove = auth()->user()->can('approve_payout_requests') || auth()->user()->can('save_investors');

        $data = $payouts->map(function ($payout) use ($canApprove) {
            $details = $payout->transaction_details ?? [];
            $beneficiaryName = $details['beneficiary_name'] ?? ($payout->user?->beneficiary_name ?? '—');
            $iban = $details['iban'] ?? ($payout->user?->iban_number ?? '—');
            $bankName = $details['bank_name'] ?? ($payout->user?->bank_name ?? '—');

            $investorHtml = '—';
            $walletUrl = null;
            if ($payout->user) {
                $investorName = e($payout->user->name ?? '—');
                $investorPhone = e($payout->user->phone ?? '');
                $walletUrl = route('admin.user-wallets.show', $payout->user->id);
                $investorHtml = "<div class='d-flex flex-column'>
                    <a href='{$walletUrl}' class='fw-bold text-primary text-decoration-none'>{$investorName}</a>
                    <small class='text-muted'><i class='ti ti-phone me-1'></i>{$investorPhone}</small>
                </div>";
            }

            $sourceBadge = '';
            if ($payout->source_commission_withdrawal_id) {
                $sourceBadge = '<span class="badge bg-label-info ms-1"><i class="ti ti-file-text me-1"></i>طلب سحب #' . $payout->source_commission_withdrawal_id . '</span>';
            } elseif ($payout->payout_type === 'IPW') {
                $sourceBadge = '<span class="badge bg-label-primary ms-1"><i class="ti ti-wallet me-1"></i>محفظة العمولات</span>';
            }

            $bankHtml = "<div class='d-flex flex-column'>
                <span class='fw-semibold text-truncate' style='max-width: 180px;' title='{$beneficiaryName}'>{$beneficiaryName}</span>
                <small class='text-muted font-monospace'>{$iban}</small>
                <small class='text-secondary'>{$bankName}</small>
            </div>";

            // Status Badge
            $statusBadge = $payout->status_badge;

            // Audit
            $auditHtml = "<div class='d-flex flex-column small'>";
            if ($payout->creator) {
                $auditHtml .= "<span class='text-muted'>بواسطة: " . e($payout->creator->name) . "</span>";
            }
            if ($payout->approved_at && $payout->approver) {
                $auditHtml .= "<span class='text-success'>اعتماد: " . e($payout->approver->name) . "</span>";
            }
            if ($payout->rejected_at && $payout->rejector) {
                $auditHtml .= "<span class='text-danger'>رفض: " . e($payout->rejector->name) . "</span>";
            }
            $auditHtml .= "</div>";

            // Actions
            $actionsHtml = "<div class='d-inline-flex gap-1'>";
            
            // View Details Button
            $actionsHtml .= "<button type='button' class='btn btn-sm btn-icon btn-label-secondary view-details-btn' data-id='{$payout->id}' title='" . __('عرض التفاصيل') . "'>
                <i class='ti ti-eye'></i>
            </button>";

            if ($payout->status === 'pending_approval' && $canApprove) {
                // Approve Button (Triggers Manager Password Modal)
                $actionsHtml .= "<button type='button' class='btn btn-sm btn-icon btn-label-success approve-btn' data-id='{$payout->id}' data-reference='{$payout->reference_id}' data-amount='{$payout->amount}' data-investor='{$beneficiaryName}' title='" . __('اعتماد وصرف HyperPay') . "'>
                    <i class='ti ti-check'></i>
                </button>";

                // Reject Button
                $actionsHtml .= "<button type='button' class='btn btn-sm btn-icon btn-label-danger reject-btn' data-id='{$payout->id}' data-reference='{$payout->reference_id}' title='" . __('رفض الطلب') . "'>
                    <i class='ti ti-x'></i>
                </button>";
            }

            $actionsHtml .= "</div>";

            return [
                'id'              => $payout->id,
                'reference_id'    => "<span class='fw-bold text-heading font-monospace'>{$payout->reference_id}</span>" . $sourceBadge,
                'investor'        => $investorHtml,
                'bank_details'    => $bankHtml,
                'amount'          => "<span class='fw-bold text-success fs-6'>" . number_format($payout->amount, 2) . "</span> <small class='text-muted'>ر.س</small>",
                'status'          => $statusBadge,
                'created_at'      => $payout->created_at->format('Y-m-d H:i'),
                'audit'           => $auditHtml,
                'actions'         => $actionsHtml,
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
     * Show full payout request details.
     */
    public function show($id)
    {
        $payout = $this->baseInvestorPayoutQuery()
            ->with(['user', 'userWallet', 'creator', 'approver', 'rejector', 'commissionWithdrawal'])
            ->findOrFail($id);

        $details = $payout->transaction_details ?? [];

        // Check for attached file (receipt/invoice)
        $attachment = null;
        if (!empty($details['image'])) {
            $filePath = $details['image'];
            $fileUrl = asset('storage/' . $filePath);
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $isPdf = ($extension === 'pdf');

            $attachment = [
                'file_url'  => $fileUrl,
                'extension' => $extension,
                'is_image'  => $isImage,
                'is_pdf'    => $isPdf,
            ];
        }

        $walletUrl = $payout->user ? route('admin.user-wallets.show', $payout->user->id) : null;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $payout->id,
                'reference_id'     => $payout->reference_id,
                'payout_id'        => $payout->payout_id ?? '—',
                'bulk_id'          => $payout->bulk_id ?? '—',
                'amount'           => number_format($payout->amount, 2),
                'status'           => $payout->status,
                'status_badge'     => $payout->status_badge,
                'payout_type'      => $payout->payout_type_name,
                'source_withdrawal_id' => $payout->source_commission_withdrawal_id,
                'created_at'       => $payout->created_at->format('Y-m-d H:i:s'),
                'created_by'       => $payout->creator?->name ?? '—',
                'approved_at'      => $payout->approved_at ? $payout->approved_at->format('Y-m-d H:i:s') : null,
                'approved_by'      => $payout->approver?->name ?? null,
                'rejected_at'      => $payout->rejected_at ? $payout->rejected_at->format('Y-m-d H:i:s') : null,
                'rejected_by'      => $payout->rejector?->name ?? null,
                'rejection_reason' => $payout->rejection_reason ?? '—',
                'failure_reason'   => $payout->failure_reason ?? '—',
                'investor'         => [
                    'id'            => $payout->user?->id,
                    'name'          => $payout->user?->name ?? '—',
                    'phone'         => $payout->user?->phone ?? '—',
                    'email'         => $payout->user?->email ?? '—',
                    'wallet_url'    => $walletUrl,
                    'balance'       => $payout->userWallet ? number_format($payout->userWallet->balance, 2) : '0.00',
                ],
                'bank_details'     => [
                    'beneficiary_name' => $details['beneficiary_name'] ?? ($payout->user?->beneficiary_name ?? '—'),
                    'iban'             => $details['iban'] ?? ($payout->user?->iban_number ?? '—'),
                    'bic'              => $details['bic'] ?? ($payout->user?->bic_code ?? '—'),
                    'bank_name'        => $details['bank_name'] ?? ($payout->user?->bank_name ?? '—'),
                    'country'          => $details['country'] ?? ($payout->user?->bank_country ?? 'SA'),
                    'city'             => $details['city'] ?? ($payout->user?->bank_city ?? 'Riyadh'),
                    'address'          => $details['address1'] ?? ($payout->user?->bank_address1 ?? ($payout->user?->address ?? '—')),
                    'purpose'          => $details['purpose'] ?? 'BA',
                ],
                'notes'            => $details['description'] ?? '—',
                'image_url'        => $attachment && $attachment['is_image'] ? $attachment['file_url'] : null,
                'attachment'       => $attachment,
                'details'          => $details,
            ]
        ]);
    }

    /**
     * Approve and execute investor payout request to HyperPay API.
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

        $payout = $this->baseInvestorPayoutQuery()
            ->with(['user', 'userWallet', 'commissionWithdrawal'])
            ->findOrFail($id);

        if ($payout->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => __('هذا الطلب ليس بحالة بانتظار المصادقة.')
            ], 422);
        }

        $user = $payout->user;
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('تعذر العثور على بيانات المستثمر المرتبط بهذا الطلب.')
            ], 422);
        }

        $details = $payout->transaction_details ?? [];
        $beneficiaryName = $details['beneficiary_name'] ?? HyperPayPayoutService::formatBeneficiaryName($user->beneficiary_name);
        $iban = str_replace(' ', '', $details['iban'] ?? $user->iban_number);
        $bic = $details['bic'] ?? $user->bic_code;
        $country = $details['country'] ?? ($user->bank_country ?? 'SA');
        $city = $details['city'] ?? ($user->bank_city ?? 'Riyadh');
        $address1 = $details['address1'] ?? ($user->bank_address1 ?? ($user->address ?: 'Riyadh'));
        $address2 = $details['address2'] ?? '.';
        $purpose = $details['purpose'] ?? 'BA';

        if (!$iban || !$bic || !$beneficiaryName) {
            return response()->json([
                'success' => false,
                'message' => __('بيانات التحويل البنكية غير مكتملة (اسم المستفيد أو الآيبان أو كود BIC).')
            ], 422);
        }

        // التحقق من صحة رقم الآيبان ومعيار Checksum قبل الإرسال
        $ibanCheck = HyperPayPayoutService::validateIbanChecksum($iban, 'المستثمر');
        if (!$ibanCheck['valid']) {
            $payout->update([
                'failure_reason' => $ibanCheck['message']
            ]);

            return response()->json([
                'success' => false,
                'message' => $ibanCheck['message']
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
            'purpose'          => $purpose,
            'description'      => "Payout Investor #{$user->id} " . substr($beneficiaryName, 0, 20)
        ]);

        if (!$payoutResponse['status']) {
            $payout->update([
                'failure_reason' => $payoutResponse['message']
            ]);

            return response()->json([
                'success' => false,
                'message' => $payoutResponse['message']
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

        if ($payout->source_commission_withdrawal_id && $payout->commissionWithdrawal) {
            $payout->commissionWithdrawal->update([
                'status' => 'processing',
                'admin_notes' => ($payout->commissionWithdrawal->admin_notes ? $payout->commissionWithdrawal->admin_notes . ' | ' : '') . "تم إرسال التحويل للبنك عبر HyperPay (رقم العملية: {$payoutId})"
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __('تمت المصادقة على طلب الدفع للمستثمر وإرسال التحويل المالي إلى HyperPay بنجاح! العملية الآن قيد المعالجة البنكية وسيتم قيد الخصم في محفظة العمولات فور تأكيدها.'),
            'data'    => [
                'payout_id' => $payoutId,
                'bulk_id'   => $bulkId,
            ]
        ]);
    }

    /**
     * Reject an investor payout request.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $payout = $this->baseInvestorPayoutQuery()->findOrFail($id);

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

        if ($payout->source_commission_withdrawal_id) {
            $withdrawal = InvestorCommissionWithdrawal::find($payout->source_commission_withdrawal_id);
            if ($withdrawal && in_array($withdrawal->status, ['pending', 'processing'])) {
                $withdrawal->update([
                    'status'           => 'rejected',
                    'rejection_reason' => $request->reason,
                    'processed_by'     => auth()->id(),
                    'processed_at'     => now(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('تم رفض طلب الدفع عبر الـ Payout للمستثمر بنجاح دون أي مساس بمحفظة العمولات.'),
        ]);
    }
}
