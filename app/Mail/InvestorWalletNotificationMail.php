<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvestorWalletNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public array $mailData;

    /**
     * Create a new message instance.
     *
     * @param array $mailData
     */
    public function __construct(array $mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->mailData['subject'] ?? 'إشعار محفظة الاستثمار والمضاربة')
            ->from(
                $this->mailData['from_email'] ?? config('mail.from.address'),
                $this->mailData['from_name'] ?? config('mail.from.name')
            )
            ->view('emails.investor.wallet-transaction', $this->mailData);
    }
}
