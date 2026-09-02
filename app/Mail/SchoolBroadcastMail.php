<?php

namespace App\Mail;

use App\Models\EmailCampaign;
use App\Models\EmailRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SchoolBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EmailCampaign $campaign,
        public EmailRecipient $recipient,
    ) {}

    public function envelope(): Envelope
    {
        $schoolEmail = trim((string) $this->campaign->school?->email);

        return new Envelope(
            replyTo: filter_var($schoolEmail, FILTER_VALIDATE_EMAIL)
                ? [new Address($schoolEmail, $this->campaign->school?->name)]
                : [],
            subject: $this->campaign->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.school-broadcast',
            text: 'emails.school-broadcast-text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
