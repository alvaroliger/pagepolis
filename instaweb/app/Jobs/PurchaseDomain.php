<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\CloudflareService;
use App\Services\NamecheapService;
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
        NamecheapService $namecheap,
        CloudflareService $cloudflare,
        NginxService $nginx
    ): void {
        try {
            if ($this->domain->type === 'custom') {
                $registrarId = $namecheap->register($this->domain->domain, [
                    'RegistrantFirstName'     => $this->domain->user->name,
                    'RegistrantLastName'      => 'InstaWeb Client',
                    'RegistrantAddress1'      => 'Calle Principal 1',
                    'RegistrantCity'          => 'Madrid',
                    'RegistrantCountry'       => 'ES',
                    'RegistrantPostalCode'    => '28001',
                    'RegistrantPhone'         => '+34.600000000',
                    'RegistrantEmailAddress'  => $this->domain->user->email,
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
