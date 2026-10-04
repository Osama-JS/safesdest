<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerInvoice;
use App\Models\Wallet;
use App\Services\CustomerInvoiceService;
use Illuminate\Support\Facades\Storage;
use Exception;

class CustomerInvoicesController extends Controller
{
    protected $invoiceService;

    public function __construct(CustomerInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * جلب قائمة الفواتير لمحفظة معينة (DataTable AJAX)
     */
    public function listByWallet(Request $request, $walletId)
    {
        $wallet = Wallet::findOrFail($walletId);

        $query = CustomerInvoice::with(['creator', 'approver', 'items'])
            ->where('wallet_id', $wallet->id);

        // الفلترة بالحالة إن وجدت
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $total = $query->count();

        // الترتيب والتقسيم
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $invoices = $query->orderBy('id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($invoices as $inv) {
            $attachmentUrl = null;
            if ($inv->attachment_file) {
                $attachmentUrl = asset('storage/' . $inv->attachment_file);
            }

            $data[] = [
                'id'                      => $inv->id,
                'invoice_number'          => $inv->invoice_number,
                'accounting_reference_no' => $inv->accounting_reference_no ?? '-',
                'issue_date'              => $inv->issue_date ? $inv->issue_date->format('Y-m-d') : '-',
                'due_date'                => $inv->due_date ? $inv->due_date->format('Y-m-d') : '-',
                'is_overdue'              => $inv->is_overdue,
                'total_amount'            => (float) $inv->total_amount,
                'paid_amount'             => (float) $inv->paid_amount,
                'remaining_amount'        => (float) $inv->remaining_amount,
                'items_count'             => $inv->items->count(),
                'status'                  => $inv->status,
                'attachment_url'          => $attachmentUrl,
                'created_by_name'         => $inv->creator ? $inv->creator->name : '-',
                'approved_by_name'        => $inv->approver ? $inv->approver->name : null,
                'approved_at'             => $inv->approved_at ? $inv->approved_at->format('Y-m-d H:i') : null,
                'created_at'              => $inv->created_at->format('Y-m-d H:i'),
                'notes'                   => $inv->notes ?? '',
            ];
        }

        return response()->json([
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    /**
     * جلب الحركات المالية غير المفوترة لمحفظة العميل
     */
    public function getUninvoicedTransactions($walletId)
    {
        try {
            $transactions = $this->invoiceService->getUninvoicedTransactions($walletId);

            $data = $transactions->map(function ($tx) {
                $taskCustom = $tx->task ? $tx->task->custom_task_number : null;
                $taskId = $tx->task_id;
                return [
                    'id'                 => $tx->id,
                    'sequence'           => $tx->sequence ?? $tx->id,
                    'amount'             => (float) $tx->amount,
                    'task_id'            => $taskId,
                    'task_number'        => $taskCustom ?: ($taskId ? ('#' . $taskId) : null),
                    'custom_task_number' => $taskCustom,
                    'description'        => $tx->description,
                    'current_maturity'   => $tx->maturity_time ? date('Y-m-d', strtotime($tx->maturity_time)) : '-',
                    'created_at'         => $tx->created_at ? $tx->created_at->format('Y-m-d H:i') : '-',
                ];
            });

            return response()->json([
                'status' => 1,
                'data'   => $data,
                'count'  => $data->count(),
                'total'  => (float) $transactions->sum('amount'),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * إنشاء فاتورة محاسبية جديدة
     */
    public function store(Request $request)
    {
        $request->validate([
            'wallet_id'               => 'required|exists:wallets,id',
            'accounting_reference_no' => 'nullable|string|max:100',
            'due_date'                => 'required|date',
            'issue_date'              => 'nullable|date',
            'transaction_ids'         => 'required|array|min:1',
            'transaction_ids.*'       => 'exists:wallet_transactions,id',
            'attachment'              => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240', // Max 10MB
            'notes'                   => 'nullable|string|max:1000',
        ]);

        try {
            $invoice = $this->invoiceService->createInvoice(
                $request->all(),
                $request->file('attachment')
            );

            return response()->json([
                'status'  => 1,
                'success' => __('Invoice :number created successfully and maturity dates updated.', ['number' => $invoice->invoice_number]),
                'invoice' => $invoice,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * تفاصيل الفاتورة
     */
    public function show($id)
    {
        try {
            $invoice = CustomerInvoice::with([
                'customer',
                'wallet',
                'items.task',
                'items.walletTransaction',
                'creator',
                'approver'
            ])->findOrFail($id);

            // الحركات غير المفوترة في نفس المحفظة لإتاحة إضافتها عند التعديل
            $uninvoiced = $this->invoiceService->getUninvoicedTransactions($invoice->wallet_id);
            $uninvoicedFormatted = $uninvoiced->map(function ($tx) {
                $taskCustom = $tx->task ? $tx->task->custom_task_number : null;
                $taskId = $tx->task_id;
                return [
                    'id'                 => $tx->id,
                    'sequence'           => $tx->sequence ?? $tx->id,
                    'amount'             => (float) $tx->amount,
                    'task_id'            => $taskId,
                    'task_number'        => $taskCustom ?: ($taskId ? ('#' . $taskId) : null),
                    'custom_task_number' => $taskCustom,
                    'description'        => $tx->description,
                    'current_maturity'   => $tx->maturity_time ? date('Y-m-d', strtotime($tx->maturity_time)) : '-',
                ];
            });

            return response()->json([
                'status'                  => 1,
                'invoice'                 => $invoice,
                'uninvoiced_transactions' => $uninvoicedFormatted,
                'attachment_url'          => $invoice->attachment_file ? asset('storage/' . $invoice->attachment_file) : null,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 404);
        }
    }

    /**
     * تعديل بيانات الفاتورة
     */
    public function update(Request $request, $id)
    {
        $invoice = CustomerInvoice::findOrFail($id);

        $request->validate([
            'accounting_reference_no' => 'nullable|string|max:100',
            'due_date'                => 'required|date',
            'issue_date'              => 'nullable|date',
            'attachment'              => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'notes'                   => 'nullable|string|max:1000',
            'transaction_ids'         => 'nullable|array|min:1',
            'transaction_ids.*'       => 'exists:wallet_transactions,id',
        ]);

        try {
            $updated = $this->invoiceService->updateInvoice(
                $invoice,
                $request->all(),
                $request->file('attachment')
            );

            return response()->json([
                'status'  => 1,
                'success' => __('Invoice :number updated successfully.', ['number' => $updated->invoice_number]),
                'invoice' => $updated,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * تسجيل سداد الفاتورة
     */
    public function pay(Request $request, $id)
    {
        $invoice = CustomerInvoice::findOrFail($id);

        $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'notes'        => 'nullable|string|max:500',
        ]);

        try {
            $paid = $this->invoiceService->markAsPaid(
                $invoice,
                (float) $request->amount,
                $request->payment_date,
                $request->notes
            );

            return response()->json([
                'status'  => 1,
                'success' => __('Payment registered successfully for invoice :number.', ['number' => $paid->invoice_number]),
                'invoice' => $paid,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * الاعتماد النهائي للفاتورة
     */
    public function approve($id)
    {
        $invoice = CustomerInvoice::findOrFail($id);

        try {
            $approved = $this->invoiceService->approveInvoice($invoice);

            return response()->json([
                'status'  => 1,
                'success' => __('Invoice :number approved finally successfully.', ['number' => $approved->invoice_number]),
                'invoice' => $approved,
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * إلغاء الفاتورة وفك الارتباط
     */
    public function cancel(Request $request, $id)
    {
        $invoice = CustomerInvoice::findOrFail($id);

        try {
            $cancelled = $this->invoiceService->cancelInvoice($invoice, $request->reason);

            return response()->json([
                'status'  => 1,
                'success' => __('Invoice :number cancelled and transactions unlinked successfully.', ['number' => $cancelled->invoice_number]),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 0, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * طباعة الفاتورة المحاسبية الرسمية
     */
    public function print($id)
    {
        $invoice = CustomerInvoice::with([
            'customer',
            'wallet',
            'items.task',
            'items.walletTransaction',
            'creator',
            'approver'
        ])->findOrFail($id);

        return view('admin.wallets.invoices.print', compact('invoice'));
    }
}
