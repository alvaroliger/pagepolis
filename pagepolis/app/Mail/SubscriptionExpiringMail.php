<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int  $daysLeft
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Te quedan {$this->daysLeft} días antes de perder tus webs ⏳"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.subscription-expiring');
    }
}
