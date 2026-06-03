<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User  $user,
        public array $stats   // ['projects' => [...], 'total_week' => int, 'total_prev_week' => int]
    ) {}

    public function envelope(): Envelope
    {
        $visits  = $this->stats['total_week'] ?? 0;
        $subject = $visits > 0
            ? "Tu web tuvo {$visits} visitas esta semana 📊"
            : 'Tu informe semanal — Pagepolis';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.weekly-report');
    }
}
