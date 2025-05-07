<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Transaction;
use Barryvdh\DomPDF\PDF;

class TransactionReceipt extends Mailable
{
  use Queueable, SerializesModels;

  public $transaction;
  public $pdf;

  public function __construct(Transaction $transaction, $pdf)
  {
    $this->transaction = $transaction;
    $this->pdf = $pdf;
  }

  public function build()
  {
    return $this->subject('إيصال الدفع')
      ->view('emails.transaction_receipt')
      ->attachData(
        $this->pdf->output(),
        'invoice_' . $this->transaction->id . '.pdf',
        ['mime' => 'application/pdf']
      );
  }
}
