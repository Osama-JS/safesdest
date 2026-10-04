<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\HyperpayPayout;
use App\Models\Teams;
use App\Models\Team_Wallet;
use App\Services\HyperPayPayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TeamPayoutRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_payout_requests', ['only' => ['index', 'getData', 'show']]);
        $this->middleware('permission:approve_payout_requests', ['only' => ['approve', 'reject']]);
    }

    /**
     * Base query for team payouts
     */
    protected function baseTeamPayoutQuery()
    {
        return HyperpayPayout::where(function ($q) {
            $q->whereNotNull('team_id')
              ->orWhereNotNull('team_wallet_id')
              ->orWhereIn('payout_type', ['TPW', 'TWM', 'TWP']);
        });
    }

    /**
     * Display the Team Payout Requests listing and metrics.
     */
    public function index()
    {
        $metrics = [
            'pending_approval_count'  => $this->baseTeamPayoutQuery()->where('status', 'pending_approval')->count(),
            'pending_approval_amount' => $this->baseTeamPayoutQuery()->where('status', 'pending_approval')->sum('amount'),
            'processing_count'        => $this->baseTeamPayoutQuery()->whereIn('status', ['pending', 'processing'])->count(),
            'processing_amount'       => $this->baseTeamPayoutQuery()->whereIn('status', ['pending', 'processing'])->sum('amount'),
            'completed_count'         => $this->baseTeamPayoutQuery()->where('status', 'completed')->count(),
            'completed_amount'        => $this->baseTeamPayoutQuery()->where('status', 'completed')->sum('amount'),
            'rejected_count'          => $this->baseTeamPayoutQuery()->where('status', 'rejected')->count(),
            'failed_count'            => $this->baseTeamPayoutQuery()->where('status', 'failed')->count(),
        ];

        $teams = Teams::select('id', 'name')->orderBy('name')->get();

        return view('admin.teams.payout_requests.index', compact('metrics', 'teams'));
    }

    /**
     * Get DataTables JSON data for Team Payout Requests.
     */
    public function getData(Request $request)
    {
        $query = $this->baseTeamPayoutQuery()
            ->with(['team', 'teamWallet', 'creator', 'approver', 'rejector'])
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'processing') {
                $query->whereIn('status', ['pending', 'processing']);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
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
                  ->orWhereHas('team', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('iban_number', 'like', "%{$search}%")
                         ->orWhere('beneficiary_name', 'like', "%{$search}%");
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
            $beneficiaryName = $details['beneficiary_name'] ?? ($payout->team?->beneficiary_name ?? '—');
            $iban = $details['iban'] ?? ($payout->team?->iban_number ?? '—');
            $bankName = $details['bank_name'] ?? ($payout->team?->bank_name ?? '—');

            $teamHtml = '—';
            $walletUrl = null;
            if ($payout->team) {
                $teamName = e($payout->team->name ?? '—');
                $teamSlug = urlencode($payout->team->name ?? 'team');
                $teamUrl = route('teams.dashboard.index', $payout->team->id);

                $walletId = $payout->team_wallet_id ?? ($payout->team->wallet->id ?? Team_Wallet::where('team_id', $payout->team->id)->value('id'));
                $walletBtn = '';
                if ($walletId) {
                    $walletUrl = route('teams.wallet', [$payout->team->id, $teamSlug]);
                    $walletBtn = '<a href="' . $walletUrl . '" target="_blank" class="badge bg-label-success text-decoration-none ms-1 py-1 px-2" title="' . __('فتح محفظة الفريق') . '"><i class="ti ti-wallet me-1"></i>' . __('المحفظة') . '</a>';
                }

                $teamHtml = "<div><div class='d-flex align-items-center flex-wrap gap-1'><a href='{$teamUrl}' target='_blank' class='fw-bold text-primary'>{$teamName}</a>{$walletBtn}</div><small class='text-muted'>" . __('فريق') . " #{$payout->team->id}</small></div>";
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

            // Open team wallet button
            if ($walletUrl) {
                $actions .= '<a href="' . $walletUrl . '" target="_blank" class="btn btn-sm btn-icon btn-label-success" title="' . __('فتح محفظة الفريق') . '"><i class="ti ti-wallet"></i></a>';
            }

            // Approve and Reject buttons (Only if pending_approval and user has permission)
            if ($payout->status === 'pending_approval' && $canApprove) {
                $actions .= '<button type="button" class="btn btn-sm btn-icon btn-label-primary btn-approve-payout" data-id="' . $payout->id . '" data-ref="' . e($payout->reference_id) . '" data-amount="' . number_format($payout->amount, 2) . '" data-beneficiary="' . e($beneficiaryName) . '" title="' . __('مصادقة وتحويل') . '"><i class="ti ti-check"></i></button>';
                $actions .= '<button type="button" class="btn btn-sm btn-icon btn-label-danger btn-reject-payout" data-id="' . $payout->id . '" data-ref="' . e($payout->reference_id) . '" title="' . __('رفض الطلب') . '"><i class="ti ti-x"></i></button>';
            }

            $actions .= '</div>';

            return [
                'id'             => $payout->id,
                'reference_id'   => '<span class="badge bg-label-dark fw-bold">' . e($payout->reference_id) . '</span>',
                'created_at'     => $payout->created_at ? $payout->created_at->format('Y-m-d H:i') : '—',
                'team'           => $teamHtml,
                'payout_type'    => '<span class="badge bg-label-secondary">' . $payout->payout_type_name . '</span>',
                'amount'         => '<span class="fw-bold text-success fs-6">' . number_format($payout->amount, 2) . '</span> <small class="text-muted">' . __('SAR') . '</small>',
                'beneficiary'    => '<div><strong>' . e($beneficiaryName) . '</strong><br><small class="text-muted font-monospace" dir="ltr">' . e($iban) . '</small><br><span class="badge bg-label-info py-0">' . e($bankName) . '</span></div>',
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
     * Show detailed information of a team payout request for modal.
     */
    public function show($id)
    {
        $payout = $this->baseTeamPayoutQuery()
            ->with(['team', 'teamWallet', 'creator', 'approver', 'rejector'])
            ->findOrFail($id);

        $details = $payout->transaction_details ?? [];

        // Team Wallet URL
        $walletUrl = null;
        if ($payout->team) {
            $teamSlug = urlencode($payout->team->name ?? 'team');
            $walletUrl = route('teams.wallet', [$payout->team->id, $teamSlug]);
        }

        // Resolve Attachment
        $rawFilePath = $details['image'] ?? ($details['receipt_image'] ?? ($details['receipt'] ?? null));
        $attachment = null;
        if (!empty($rawFilePath)) {
            $extension = strtolower(pathinfo($rawFilePath, PATHINFO_EXTENSION));
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
            $isPdf = ($extension === 'pdf');

            if (str_starts_with($rawFilePath, 'http://') || str_starts_with($rawFilePath, 'https://')) {
                $fileUrl = $rawFilePath;
            } else {
                $cleanPath = ltrim(str_replace('storage/', '', $rawFilePath), '/');
                $fileUrl = url('storage/' . $cleanPath);
            }

            $attachment = [
                'has_file'  => true,
                'path'      => $rawFilePath,
                'file_name' => basename($rawFilePath),
                'file_url'  => $fileUrl,
                'extension' => $extension,
                'is_image'  => $isImage,
                'is_pdf'    => $isPdf,
            ];
        }

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
                'team'             => [
                    'id'            => $payout->team?->id,
                    'name'          => $payout->team?->name ?? '—',
                    'wallet_url'    => $walletUrl,
                ],
                'bank_details'     => [
                    'beneficiary_name' => $details['beneficiary_name'] ?? ($payout->team?->beneficiary_name ?? '—'),
                    'iban'             => $details['iban'] ?? ($payout->team?->iban_number ?? '—'),
                    'bic'              => $details['bic'] ?? ($payout->team?->bic_code ?? '—'),
                    'bank_name'        => $details['bank_name'] ?? ($payout->team?->bank_name ?? '—'),
                    'country'          => $details['country'] ?? ($payout->team?->bank_country ?? 'SA'),
                    'city'             => $details['city'] ?? ($payout->team?->bank_city ?? 'Riyadh'),
                    'address'          => $details['address1'] ?? ($payout->team?->bank_address1 ?? ($payout->team?->address ?? '—')),
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
     * Approve and execute team payout request to HyperPay API.
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

        $payout = $this->baseTeamPayoutQuery()
            ->with(['team', 'teamWallet'])
            ->findOrFail($id);

        if ($payout->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => __('هذا الطلب ليس بحالة بانتظار المصادقة.')
            ], 422);
        }

        $team = $payout->team;
        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => __('تعذر العثور على بيانات الفريق المرتبط بهذا الطلب.')
            ], 422);
        }

        $details = $payout->transaction_details ?? [];
        $beneficiaryName = $details['beneficiary_name'] ?? HyperPayPayoutService::formatBeneficiaryName($team->beneficiary_name);
        $iban = str_replace(' ', '', $details['iban'] ?? $team->iban_number);
        $bic = $details['bic'] ?? $team->bic_code;
        $country = $details['country'] ?? ($team->bank_country ?? 'SA');
        $city = $details['city'] ?? ($team->bank_city ?? 'Riyadh');
        $address1 = $details['address1'] ?? ($team->bank_address1 ?? ($team->address ?: 'Riyadh'));
        $address2 = $details['address2'] ?? '.';
        $purpose = $details['purpose'] ?? 'BA';

        if (!$iban || !$bic || !$beneficiaryName) {
            return response()->json([
                'success' => false,
                'message' => __('بيانات التحويل البنكية غير مكتملة (اسم المستفيد أو الآيبان أو كود BIC).')
            ], 422);
        }

        // التحقق من صحة رقم الآيبان ومعيار Checksum قبل الإرسال
        $ibanCheck = HyperPayPayoutService::validateIbanChecksum($iban, 'الفريق');
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
            'description'      => "Payout Team #{$team->id} " . substr($beneficiaryName, 0, 20)
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

        return response()->json([
            'success' => true,
            'message' => __('تمت المصادقة على طلب الدفع للفريق وإرسال التحويل المالي إلى هايبر باي بنجاح! العملية الآن قيد المعالجة البنكية وسيتم قيد الخصم في المحفظة فور تأكيدها.'),
            'data'    => [
                'payout_id' => $payoutId,
                'bulk_id'   => $bulkId,
            ]
        ]);
    }

    /**
     * Reject a team payout request.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $payout = $this->baseTeamPayoutQuery()->findOrFail($id);

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

        return response()->json([
            'success' => true,
            'message' => __('تم رفض طلب الدفع عبر الـ Payout للفريق بنجاح دون أي مساس بمحفظة الفريق.'),
        ]);
    }
}
