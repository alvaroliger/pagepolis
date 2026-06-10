<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiringMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExpiryReminders extends Command
{
    protected $signature   = 'pagepolis:expiry-reminders';
    protected $description = 'Avisa por email a los usuarios cuyo periodo de gracia termina en ~7 días';

    public function handle(): void
    {
        // Usuarios cuyo periodo de gracia termina dentro de 7 días (ventana de 1 día,
        // ya que el comando corre a diario, así cada usuario recibe el aviso una vez).
        $from = now()->addDays(7)->startOfDay();
        $to   = now()->addDays(7)->endOfDay();

        $users = User::whereNotNull('grace_period_ends_at')
            ->whereBetween('grace_period_ends_at', [$from, $to])
            ->get();

        foreach ($users as $user) {
            try {
                $daysLeft = max(1, (int) ceil(now()->diffInDays($user->grace_period_ends_at, false)));
                Mail::to($user->email)->queue(new SubscriptionExpiringMail($user, $daysLeft));
                $this->info("Aviso enviado a {$user->email} ({$daysLeft} días).");
            } catch (\Exception $e) {
                Log::error("Error enviando aviso de expiración a {$user->email}: " . $e->getMessage());
            }
        }

        $this->info("Avisos de expiración procesados: {$users->count()}.");
    }
}
