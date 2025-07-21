<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class NotificationMail extends Mailable
{
  use Queueable, SerializesModels;

  public $emailData;
  public $attachments;

  /**
   * Create a new message instance.
   *
   * @param array $emailData
   * @param array $attachments
   */
  public function __construct(array $emailData, array $attachments = [])
  {
    $this->emailData = $emailData;
    $this->attachments = $attachments;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {
    $email = $this->subject($this->emailData['subject'])
      ->from(
        $this->emailData['from_email'] ?? config('mail.from.address'),
        $this->emailData['from_name'] ?? config('mail.from.name')
      );

    // Set reply-to if provided
    if (isset($this->emailData['reply_to'])) {
      $email->replyTo($this->emailData['reply_to']);
    }

    // Set CC if provided
    if (isset($this->emailData['cc']) && is_array($this->emailData['cc'])) {
      $email->cc($this->emailData['cc']);
    }

    // Set BCC if provided
    if (isset($this->emailData['bcc']) && is_array($this->emailData['bcc'])) {
      $email->bcc($this->emailData['bcc']);
    }

    // Determine the view template
    $template = $this->emailData['template'] ?? 'emails.notification';

    // Set the view with data
    $email->view($template, [
      'data' => $this->emailData,
      'subject' => $this->emailData['subject'],
      'content' => $this->emailData['content'] ?? '',
      'user_name' => $this->emailData['user_name'] ?? 'User',
      'action_url' => $this->emailData['action_url'] ?? null,
      'action_text' => $this->emailData['action_text'] ?? null,
      'additional_data' => $this->emailData['additional_data'] ?? []
    ]);

    // Add attachments
    if (!empty($this->attachments)) {
      foreach ($this->attachments as $attachment) {
        if (isset($attachment['file'])) {
          // File path attachment
          if (Storage::exists($attachment['file'])) {
            $email->attach(
              Storage::path($attachment['file']),
              [
                'as' => $attachment['as'] ?? basename($attachment['file']),
                'mime' => $attachment['mime'] ?? null
              ]
            );
          }
        } elseif (isset($attachment['data'])) {
          // Raw data attachment
          $email->attachData(
            $attachment['data'],
            $attachment['as'] ?? 'attachment.pdf',
            [
              'mime' => $attachment['mime'] ?? 'application/octet-stream'
            ]
          );
        } elseif (isset($attachment['url'])) {
          // URL attachment
          $email->attach(
            $attachment['url'],
            [
              'as' => $attachment['as'] ?? 'attachment',
              'mime' => $attachment['mime'] ?? null
            ]
          );
        }
      }
    }

    return $email;
  }
}
