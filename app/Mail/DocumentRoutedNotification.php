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

    public function __construct($document, $recipientName)
    {
        $this->document = $document;
        $this->recipientName = $recipientName;
    }

    public function build()
    {
        return $this->subject('New Document Routed to You')
                    ->view('emails.routed_notification');
    }
}
