<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CpiSyncFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $reason,
        public readonly Carbon $periodFrom,
        public readonly Carbon $periodTo,
        public readonly Carbon $failedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cpi_alert.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.cpi-sync-failed',
        );
    }
}
