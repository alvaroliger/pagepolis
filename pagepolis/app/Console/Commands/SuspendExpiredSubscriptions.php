<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Jobs\SuspendSite;
use App\Services\DinahostingService;
use App\Services\NginxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SuspendExpiredSubscriptions extends Command
{
    protected $signature   = 'pagepolis:suspend-expired';
    protected $description = 'Suspende y elimina datos de usuarios con periodo de gracia expirado';

    public function handle(): void
    {
        User::whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now())
            ->whereNull('admin_suspended_at')
            ->each(function (User $user) {
                try {
                    $user->update(['grace_period_ends_at' => null]);

                    foreach ($user->projects as $project) {
                        $project->delete();
                    }

                    $dinahosting = app(DinahostingService::class);
                    $nginx       = app(NginxService::class);

                    foreach ($user->domains as $domain) {
                        if ($domain->type === 'custom' && $domain->registrar_id) {
                            $dinahosting->release($domain->domain);
                        }
                        $nginx->removeVHost($domain->domain);
                        $domain->update(['status' => 'released']);
                    }

                    $user->delete();
                    Log::info("Usuario {$user->email} eliminado por impago (gracia expirada).");
                } catch (\Exception $e) {
                    Log::error("Error suspendiendo usuario {$user->email}: " . $e->getMessage());
                }
            });
    }
}
