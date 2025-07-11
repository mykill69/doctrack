<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentRoutedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $recipientName;
    public $transRemarks;

    public function __construct($document, $recipientName, $transRemarks)
    {
        $this->document = $document;
        $this->recipientName = $recipientName;
        $this->transRemarks = $transRemarks;
    }

    public function build()
    {
        return $this->subject('Document Receipt Notification')
                    ->view('emails.routed_notification');
    }
}
