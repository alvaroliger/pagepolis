<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CloudflareService
{
    private string $apiToken;
    private string $zoneId;
    private string $baseUrl = 'https://api.cloudflare.com/client/v4';

    public function __construct()
    {
        $this->apiToken = config('services.cloudflare.token');
        $this->zoneId   = config('services.cloudflare.zone_id');
    }

    public function addARecord(string $domain, string $ip): string
    {
        $response = Http::withToken($this->apiToken)
            ->post("{$this->baseUrl}/zones/{$this->zoneId}/dns_records", [
                'type'    => 'A',
                'name'    => $domain,
                'content' => $ip,
                'ttl'     => 3600,
                'proxied' => true,
            ]);

        $data = $response->json();

        if (!$data['success']) {
            throw new \Exception('Error creando registro DNS: ' . json_encode($data['errors']));
        }

        return $data['result']['id'];
    }

    public function deleteRecord(string $recordId): void
    {
        Http::withToken($this->apiToken)
            ->delete("{$this->baseUrl}/zones/{$this->zoneId}/dns_records/{$recordId}");
    }

    public function addSubdomainRecord(string $subdomain, string $ip): string
    {
        return $this->addARecord($subdomain . '.' . config('app.sites_base_domain'), $ip);
    }
}
