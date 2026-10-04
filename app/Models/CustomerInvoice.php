<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInvoice extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'customer_invoices';

    protected $fillable = [
        'invoice_number',
        'accounting_reference_no',
        'customer_id',
        'wallet_id',
        'issue_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status', // unpaid, paid, approved, cancelled
        'payment_date',
        'attachment_file',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'issue_date'       => 'date',
        'due_date'         => 'date',
        'payment_date'     => 'datetime',
        'approved_at'      => 'datetime',
        'total_amount'     => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    /**
     * العلاقات
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function items()
    {
        return $this->hasMany(CustomerInvoiceItem::class, 'customer_invoice_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(Wallet_Transaction::class, 'customer_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * التحقق مما إذا كانت الفاتورة مستحقة ومتأخرة عن السداد
     */
    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['paid', 'approved', 'cancelled'])) {
            return false;
        }
        return $this->due_date && $this->due_date->isPast();
    }

    /**
     * توليد الرقم التسلسلي للفاتورة بالصيغة: INV-YYYY-XXXX (مثال: INV-2026-0001)
     */
    public static function generateNextInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";

        $lastInvoice = self::withTrashed()
            ->where('invoice_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->invoice_number);
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $nextSeq = (int) $parts[2] + 1;
            }
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
