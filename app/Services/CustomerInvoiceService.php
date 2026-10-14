<?php

namespace App\Services;

use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\Wallet;
use App\Models\Wallet_Transaction;
use App\Helpers\FileHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;

class CustomerInvoiceService
{
    /**
     * جلب الحركات المالية غير المفوترة لمحفظة العميل
     */
    public function getUninvoicedTransactions(int $walletId)
    {
        return Wallet_Transaction::where('wallet_id', $walletId)
            ->where('transaction_type', 'debit')
            ->whereNull('customer_invoice_id')
            ->with(['task'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * إنشاء فاتورة محاسبية جديدة وربط الحركات وتعميم تاريخ الاستحقاق
     *
     * @throws Exception
     */
    public function createInvoice(array $data, ?object $attachmentFile = null): CustomerInvoice
    {
        $wallet = Wallet::findOrFail($data['wallet_id']);
        if ($wallet->user_type !== 'customer' || empty($wallet->customer_id)) {
            throw new Exception(__('Only customer wallets support accounting invoices.'));
        }

        $transactionIds = $data['transaction_ids'] ?? [];
        if (empty($transactionIds) || !is_array($transactionIds)) {
            throw new Exception(__('Please select at least one debit transaction to include in the invoice.'));
        }

        return DB::transaction(function () use ($wallet, $data, $transactionIds, $attachmentFile) {
            // جلب الحركات والتحقق من أنها تابعة للمحفظة وغير مفوترة
            $transactions = Wallet_Transaction::whereIn('id', $transactionIds)
                ->where('wallet_id', $wallet->id)
                ->where('transaction_type', 'debit')
                ->whereNull('customer_invoice_id')
                ->lockForUpdate()
                ->get();

            if ($transactions->count() !== count($transactionIds)) {
                throw new Exception(__('Some selected transactions are already invoiced or do not belong to this wallet.'));
            }

            $totalAmount = (float) $transactions->sum('amount');
            $dueDate = Carbon::parse($data['due_date'])->toDateString();
            $issueDate = !empty($data['issue_date']) ? Carbon::parse($data['issue_date'])->toDateString() : now()->toDateString();

            if (Carbon::parse($dueDate)->startOfDay()->lte(Carbon::parse($issueDate)->startOfDay())) {
                throw new Exception(__('The due date must be greater than the invoice issue date (:date).', ['date' => $issueDate]));
            }

            // رفع الملف إن وُجد
            $filePath = null;
            if ($attachmentFile) {
                $filePath = FileHelper::uploadFile($attachmentFile, 'customer_invoices');
            }

            // توليد رقم الفاتورة التسلسلي INV-YYYY-XXXX
            $invoiceNumber = CustomerInvoice::generateNextInvoiceNumber();

            $invoice = CustomerInvoice::create([
                'invoice_number'          => $invoiceNumber,
                'accounting_reference_no' => $data['accounting_reference_no'] ?? null,
                'customer_id'             => $wallet->customer_id,
                'wallet_id'               => $wallet->id,
                'issue_date'              => $issueDate,
                'due_date'                => $dueDate,
                'total_amount'            => $totalAmount,
                'paid_amount'             => 0.00,
                'remaining_amount'        => $totalAmount,
                'status'                  => 'unpaid',
                'attachment_file'         => $filePath,
                'notes'                   => $data['notes'] ?? null,
                'created_by'              => Auth::id(),
            ]);

            // ربط كل حركة بالفاتورة وتعميم تاريخ الاستحقاق
            foreach ($transactions as $tx) {
                CustomerInvoiceItem::create([
                    'customer_invoice_id'    => $invoice->id,
                    'wallet_transaction_id'  => $tx->id,
                    'task_id'                => $tx->task_id,
                    'amount'                 => $tx->amount,
                    'previous_maturity_time' => $tx->maturity_time,
                ]);

                // تحديث الحركة: تعميم تاريخ الاستحقاق + ربط الفاتورة
                $tx->update([
                    'customer_invoice_id' => $invoice->id,
                    'maturity_time'       => Carbon::parse($dueDate)->endOfDay(),
                ]);
            }

            return $invoice;
        });
    }

    /**
     * تحديث بيانات الفاتورة وتعميم تاريخ الاستحقاق الجديد على الحركات
     *
     * @throws Exception
     */
    public function updateInvoice(CustomerInvoice $invoice, array $data, ?object $attachmentFile = null, bool $isForceEdit = false): CustomerInvoice
    {
        if ($invoice->status === 'paid') {
            throw new Exception(__('Paid and closed invoices cannot be modified.'));
        }

        if ($invoice->status === 'cancelled') {
            throw new Exception(__('Cancelled invoices cannot be modified.'));
        }

        if ($invoice->status === 'approved') {
            if (!$isForceEdit || !Auth::check() || !Auth::user()->can('force_edit_customer_invoices')) {
                throw new Exception(__('This invoice is already approved. Force edit permission is required to modify it.'));
            }
        } elseif ($invoice->status !== 'unpaid') {
            throw new Exception(__('Only unpaid or approved invoices can be modified.'));
        }

        return DB::transaction(function () use ($invoice, $data, $attachmentFile, $isForceEdit) {
            $oldDueDate = $invoice->due_date ? $invoice->due_date->toDateString() : null;
            $newDueDate = isset($data['due_date']) ? Carbon::parse($data['due_date'])->toDateString() : $oldDueDate;
            $targetIssueDate = !empty($data['issue_date'])
                ? Carbon::parse($data['issue_date'])->toDateString()
                : ($invoice->issue_date ? $invoice->issue_date->toDateString() : Carbon::today()->toDateString());

            if ($newDueDate && Carbon::parse($newDueDate)->startOfDay()->lte(Carbon::parse($targetIssueDate)->startOfDay())) {
                throw new Exception(__('The due date must be greater than the invoice issue date (:date).', ['date' => $targetIssueDate]));
            }

            $notes = $data['notes'] ?? $invoice->notes;
            if ($isForceEdit && !empty($data['force_edit_reason'])) {
                $notes = ($notes ? ($notes . "\n") : '') . '【' . __('Force Edit Reason: :reason', ['reason' => $data['force_edit_reason']]) . ' (' . (Auth::user() ? Auth::user()->name : '') . ' - ' . now()->format('Y-m-d H:i') . ')】';
            }

            $updateData = [
                'accounting_reference_no' => $data['accounting_reference_no'] ?? $invoice->accounting_reference_no,
                'due_date'                => $newDueDate,
                'notes'                   => $notes,
            ];

            if (!empty($data['issue_date'])) {
                $updateData['issue_date'] = Carbon::parse($data['issue_date'])->toDateString();
            }

            if ($attachmentFile) {
                $updateData['attachment_file'] = FileHelper::uploadFile($attachmentFile, 'customer_invoices');
            }

            // إذا تم تمرير قائمة الحركات لتعديل الارتباط (فصل حركات أو إضافة حركات)
            if (isset($data['transaction_ids']) && is_array($data['transaction_ids'])) {
                $selectedIds = array_map('intval', $data['transaction_ids']);

                if (empty($selectedIds)) {
                    throw new Exception(__('The invoice must contain at least one linked transaction.'));
                }

                $currentItems = CustomerInvoiceItem::where('customer_invoice_id', $invoice->id)->get();
                $currentItemTxIds = $currentItems->pluck('wallet_transaction_id')->toArray();

                // 1. فصل الارتباط عن الحركات المستبعدة وإعادة تاريخ الاستحقاق السابق
                $toRemoveTxIds = array_diff($currentItemTxIds, $selectedIds);
                foreach ($toRemoveTxIds as $txId) {
                    $item = $currentItems->firstWhere('wallet_transaction_id', $txId);
                    $tx = Wallet_Transaction::find($txId);
                    if ($tx) {
                        $tx->update([
                            'customer_invoice_id' => null,
                            'maturity_time'       => $item ? $item->previous_maturity_time : null,
                        ]);
                    }
                    if ($item) {
                        $item->delete();
                    }
                }

                // 2. ربط الحركات الجديدة المضافة وتعميم تاريخ الاستحقاق
                $toAddTxIds = array_diff($selectedIds, $currentItemTxIds);
                if (!empty($toAddTxIds)) {
                    $newTransactions = Wallet_Transaction::whereIn('id', $toAddTxIds)
                        ->where('wallet_id', $invoice->wallet_id)
                        ->where('transaction_type', 'debit')
                        ->whereNull('customer_invoice_id')
                        ->lockForUpdate()
                        ->get();

                    if ($newTransactions->count() !== count($toAddTxIds)) {
                        throw new Exception(__('Some selected transactions are already invoiced or do not belong to this wallet.'));
                    }

                    foreach ($newTransactions as $newTx) {
                        CustomerInvoiceItem::create([
                            'customer_invoice_id'    => $invoice->id,
                            'wallet_transaction_id'  => $newTx->id,
                            'task_id'                => $newTx->task_id,
                            'amount'                 => $newTx->amount,
                            'previous_maturity_time' => $newTx->maturity_time,
                        ]);

                        $newTx->update([
                            'customer_invoice_id' => $invoice->id,
                            'maturity_time'       => Carbon::parse($newDueDate)->endOfDay(),
                        ]);
                    }
                }

                // إعادة احتساب إجمالي الفاتورة والمبلغ المتبقي
                $totalAmount = (float) CustomerInvoiceItem::where('customer_invoice_id', $invoice->id)->sum('amount');
                $updateData['total_amount'] = $totalAmount;
                $updateData['remaining_amount'] = max(0, $totalAmount - (float) $invoice->paid_amount);
                if ($updateData['remaining_amount'] <= 0 && $totalAmount > 0) {
                    $updateData['status'] = 'paid';
                } elseif ($invoice->status === 'paid' && $updateData['remaining_amount'] > 0) {
                    $updateData['status'] = 'approved';
                }
            }

            if ($isForceEdit) {
                $invoice->customActivityAction = 'تعديل بالقوة على الفاتورة';
                $invoice->customActivityExtraNew = [
                    'force_edit'        => true,
                    'force_edit_reason' => $data['force_edit_reason'] ?? null,
                    'admin_user_id'     => Auth::id(),
                    'admin_user_name'   => Auth::user() ? Auth::user()->name : null,
                ];
            }

            $invoice->update($updateData);

            // إذا تغيّر تاريخ الاستحقاق، يتم تعميمه فوراً على كافة حركات المحفظة المربوطة حالياً
            if ($newDueDate && $newDueDate !== $oldDueDate) {
                Wallet_Transaction::where('customer_invoice_id', $invoice->id)
                    ->update(['maturity_time' => Carbon::parse($newDueDate)->endOfDay()]);
            }

            return $invoice;
        });
    }

    /**
     * تسجيل سداد الفاتورة (كلي أو جزئي) بعد اعتمادها
     *
     * @throws Exception
     */
    public function markAsPaid(CustomerInvoice $invoice, float $amount, ?string $paymentDate = null, ?string $notes = null): CustomerInvoice
    {
        if ($invoice->status === 'unpaid') {
            throw new Exception(__('Invoice must be approved first before registering payment.'));
        }

        if ($invoice->status === 'paid') {
            throw new Exception(__('Invoice is already fully paid and closed.'));
        }

        if ($invoice->status === 'cancelled') {
            throw new Exception(__('Cannot make payment for a cancelled invoice.'));
        }

        if ($invoice->status !== 'approved') {
            throw new Exception(__('Payment is only allowed for approved invoices.'));
        }

        if ($amount <= 0) {
            throw new Exception(__('Payment amount must be greater than zero.'));
        }

        return DB::transaction(function () use ($invoice, $amount, $paymentDate, $notes) {
            $newPaid = (float) $invoice->paid_amount + $amount;
            $remaining = max(0, (float) $invoice->total_amount - $newPaid);
            // إذا تم سداد كامل المبلغ تُقفل الفاتورة نهائياً وتصبح حالتها 'paid'
            // إذا كان السداد جزئياً، تظل الحالة 'approved' بانتظار استكمال السداد
            $status = ($remaining <= 0) ? 'paid' : 'approved';

            $invoice->update([
                'paid_amount'      => min($invoice->total_amount, $newPaid),
                'remaining_amount' => $remaining,
                'status'           => $status,
                'payment_date'     => $paymentDate ? Carbon::parse($paymentDate) : now(),
                'notes'            => $notes ? ($invoice->notes . "\n" . $notes) : $invoice->notes,
            ]);

            return $invoice;
        });
    }

    /**
     * اعتماد الفاتورة في النظام لتصبح رسمية وسارية بانتظار السداد
     *
     * @throws Exception
     */
    public function approveInvoice(CustomerInvoice $invoice): CustomerInvoice
    {
        if ($invoice->status === 'approved') {
            throw new Exception(__('Invoice is already approved.'));
        }

        if ($invoice->status === 'paid') {
            throw new Exception(__('Invoice is already paid and closed.'));
        }

        if ($invoice->status === 'cancelled') {
            throw new Exception(__('Cannot approve a cancelled invoice.'));
        }

        $invoice->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $invoice;
    }

    /**
     * إلغاء الفاتورة وفك ارتباط حركات المحفظة واسترجاع تاريخ الاستحقاق السابق
     *
     * @throws Exception
     */
    public function cancelInvoice(CustomerInvoice $invoice, ?string $reason = null): CustomerInvoice
    {
        if ($invoice->status === 'paid') {
            throw new Exception(__('Paid and closed invoices cannot be cancelled.'));
        }

        if ((float) $invoice->paid_amount > 0) {
            throw new Exception(__('Invoices with registered payments cannot be cancelled.'));
        }

        return DB::transaction(function () use ($invoice, $reason) {
            $items = CustomerInvoiceItem::where('customer_invoice_id', $invoice->id)->get();

            foreach ($items as $item) {
                // فك الارتباط واستعادة تاريخ الاستحقاق السابق
                Wallet_Transaction::where('id', $item->wallet_transaction_id)->update([
                    'customer_invoice_id' => null,
                    'maturity_time'       => $item->previous_maturity_time,
                ]);
            }

            $invoice->update([
                'status' => 'cancelled',
                'notes'  => $reason ? ($invoice->notes . " | سبب الإلغاء: " . $reason) : $invoice->notes,
            ]);

            return $invoice;
        });
    }
}
