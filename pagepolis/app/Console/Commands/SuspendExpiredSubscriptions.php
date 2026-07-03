<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DinahostingService;
use App\Services\NginxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
                    $dinahosting = app(DinahostingService::class);
                    $nginx       = app(NginxService::class);

                    // External service cleanup first: if this throws, grace_period_ends_at
                    // is preserved and the next daily run retries cleanly rather than
                    // leaving the user stuck with a null grace period and no soft-delete.
                    foreach ($user->domains as $domain) {
                        if ($domain->type === 'custom' && $domain->registrar_id) {
                            $dinahosting->release($domain->domain);
                        }
                        $nginx->removeVHost($domain->domain);
                    }

                    // DB cleanup in a transaction once external resources are released.
                    DB::transaction(function () use ($user) {
                        foreach ($user->domains as $domain) {
                            $domain->update(['status' => 'released']);
                        }
                        foreach ($user->projects as $project) {
                            $project->delete();
                        }
                        $user->update(['grace_period_ends_at' => null]);
                        $user->delete();
                    });

                    Log::info("Usuario {$user->email} eliminado por impago (gracia expirada).");
                } catch (\Exception $e) {
                    Log::error("Error suspendiendo usuario {$user->email}: " . $e->getMessage());
                }
            });
    }
}
