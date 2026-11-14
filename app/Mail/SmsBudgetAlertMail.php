<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SmsBudgetAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly int $sent,
        public readonly int $limit,
        public readonly bool $exhausted,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __($this->exhausted ? 'sms_alert.exhausted_subject' : 'sms_alert.warning_subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.sms-budget-alert',
        );
    }
}
