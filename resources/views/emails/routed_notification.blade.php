<!DOCTYPE html>
<html>
<head>
    <title>New Routed Document</title>
</head>
<body>
    <p>Dear {{ $recipientName }},</p>
    <p>A new document has been routed to you.</p>
    <p><strong>Subject:</strong> {{ $document->subject }}</p>
    <p><strong>From:</strong> {{ $document->full_name }}</p>
    <p><strong>Document Type:</strong> {{ $document->doc_type }}</p>

    <p>You may now view it in the document tracking system.</p>

<p>
     <a href="{{ url('/') }}" style="color: #1a73e8; text-decoration: none;">
        Click here to view the document
    </a>
</p>

<p style="margin-top: 25px;">
    Regards,<br>
    <strong>Records Office Team</strong><br>
    Central Philippines State University
</p>

<hr style="margin-top: 40px;">
<p style="font-size: 12px; color: #888;">
    This is an automated email. Please do not reply directly to this message.
</p>

