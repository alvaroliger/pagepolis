<?php

namespace App\Console\Commands;

use App\Mail\NudgePublishMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendOnboardingNudges extends Command
{
    protected $signature   = 'pagepolis:onboarding-nudges';
    protected $description = 'Envía un recordatorio a usuarios sin web publicada a las 48 h del registro';

    public function handle(): void
    {
        // Users registered between 48 h and 72 h ago who have no published project.
        // The 24 h window ensures each user receives the nudge exactly once
        // even if the command runs daily.
        $users = User::whereBetween('created_at', [now()->subHours(72), now()->subHours(48)])
            ->whereDoesntHave('projects', fn ($q) => $q->where('status', 'published'))
            ->get();

        $this->info("Enviando nudge a {$users->count()} usuario(s)...");

        foreach ($users as $user) {
            try {
                Mail::to($user->email)->queue(new NudgePublishMail($user));
                $this->line("  ✓ {$user->email}");
            } catch (\Exception $e) {
                $this->error("  ✗ {$user->email}: " . $e->getMessage());
            }
        }

        $this->info('Nudges enviados.');
    }
}
