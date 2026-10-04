<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class CustomerInvoiceItem extends Model
{
    use LogsActivity;

    protected $table = 'customer_invoice_items';

    protected $fillable = [
        'customer_invoice_id',
        'wallet_transaction_id',
        'task_id',
        'amount',
        'previous_maturity_time',
    ];

    protected $casts = [
        'amount'                 => 'decimal:2',
        'previous_maturity_time' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id');
    }

    public function walletTransaction()
    {
        return $this->belongsTo(Wallet_Transaction::class, 'wallet_transaction_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
