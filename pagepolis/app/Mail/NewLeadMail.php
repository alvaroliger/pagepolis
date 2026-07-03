<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public Lead $lead,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = [];
        if ($this->lead->email) {
            $replyTo[] = new Address($this->lead->email, $this->lead->name);
        }

        $from = $this->lead->name ? "de {$this->lead->name} " : '';

        return new Envelope(
            subject: "Nuevo mensaje {$from}desde «{$this->project->name}» 📩",
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-lead');
    }
}
