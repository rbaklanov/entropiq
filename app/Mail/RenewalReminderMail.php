<?php

namespace App\Mail;

use App\Models\Subscription;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RenewalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Subscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('payments.reminder_subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.renewal-reminder',
            with: [
                'amount' => Money::amount($this->subscription->plan->price()),
                'date' => $this->subscription->ends_at?->translatedFormat('j F Y'),
                'subscriptionUrl' => route('settings.subscription'),
            ],
        );
    }
}
