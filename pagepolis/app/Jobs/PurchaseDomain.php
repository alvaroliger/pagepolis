<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\CloudflareService;
use App\Services\DinahostingService;
use App\Services\NginxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PurchaseDomain implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Domain $domain) {}

    public function handle(
        DinahostingService $dinahosting,
        CloudflareService $cloudflare,
        NginxService $nginx
    ): void {
        try {
            if ($this->domain->type === 'custom') {
                $registrarId = $dinahosting->register($this->domain->domain, [
                    'firstname' => $this->domain->user->name,
                    'lastname'  => 'Pagepolis Client',
                    'address'   => 'Calle Principal 1',
                    'city'      => 'Madrid',
                    'country'   => 'ES',
                    'zip'       => '28001',
                    'phone'     => '+34.600000000',
                    'email'     => $this->domain->user->email,
                ]);

                $recordId = $cloudflare->addARecord(
                    $this->domain->domain,
                    config('services.server.public_ip')
                );
            } else {
                $registrarId = null;
                $recordId    = $cloudflare->addSubdomainRecord(
                    $this->domain->domain,
                    config('services.server.public_ip')
                );
            }

            $nginx->createVHost($this->domain->domain, $this->domain->project_id);

            $this->domain->update([
                'status'               => 'active',
                'registrar_id'         => $registrarId,
                'cloudflare_record_id' => $recordId,
                'expires_at'           => now()->addYear(),
            ]);

            DeploySite::dispatch($this->domain->project);

        } catch (\Exception $e) {
            Log::error('PurchaseDomain failed: ' . $e->getMessage());
            $this->fail($e);
        }
    }
}
