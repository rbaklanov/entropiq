<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class CpiDataStaleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly ?Carbon $latestPublished,
        public readonly Carbon $expectedAtLeast,
        public readonly Carbon $checkedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('cpi_alert.stale_subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.cpi-data-stale',
        );
    }
}
