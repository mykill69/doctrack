<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DoctrackNotification extends Mailable
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
       return $this->subject('Document Tracking Notification w/ ref.# ' . $this->document->docslip_id)
                    ->view('emails.doctrack_notification');
    }
}
