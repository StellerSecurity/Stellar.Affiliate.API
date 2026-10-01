<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliatePayoutDetailsReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $affiliateName,
        public readonly bool $test = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->test ? '[TEST] ' : '').'Action required: Add your bank details for affiliate payouts');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.affiliate-payout-details',
            with: ['payoutUrl' => url('/affiliate/payouts')],
        );
    }
}
