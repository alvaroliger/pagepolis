<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NamecheapService
{
    private string $apiUser;
    private string $apiKey;
    private string $clientIp;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiUser  = config('services.namecheap.api_user');
        $this->apiKey   = config('services.namecheap.api_key');
        $this->clientIp = config('services.namecheap.client_ip');
        $this->baseUrl  = config('services.namecheap.sandbox')
            ? 'https://api.sandbox.namecheap.com/xml.response'
            : 'https://api.namecheap.com/xml.response';
    }

    public function checkAvailability(string $domain): array
    {
        $response = Http::get($this->baseUrl, [
            'ApiUser'    => $this->apiUser,
            'ApiKey'     => $this->apiKey,
            'UserName'   => $this->apiUser,
            'ClientIp'   => $this->clientIp,
            'Command'    => 'namecheap.domains.check',
            'DomainList' => $domain,
        ]);

        $xml       = simplexml_load_string($response->body());
        $available = (string) $xml->CommandResponse->DomainCheckResult['Available'] === 'true';
        $price     = (float) ($xml->CommandResponse->DomainCheckResult['PremiumRegistrationPrice'] ?? 12.00);

        return ['available' => $available, 'price' => $price];
    }

    public function register(string $domain, array $contactInfo): string
    {
        $response = Http::get($this->baseUrl, array_merge([
            'ApiUser'     => $this->apiUser,
            'ApiKey'      => $this->apiKey,
            'UserName'    => $this->apiUser,
            'ClientIp'    => $this->clientIp,
            'Command'     => 'namecheap.domains.create',
            'DomainName'  => $domain,
            'Years'       => 1,
            'Nameservers' => 'ns1.cloudflare.com,ns2.cloudflare.com',
        ], $contactInfo));

        $xml = simplexml_load_string($response->body());

        if ((string) $xml->CommandResponse->DomainCreateResult['Registered'] !== 'true') {
            throw new \Exception('Error registrando dominio: ' . $domain);
        }

        return (string) $xml->CommandResponse->DomainCreateResult['DomainID'];
    }

    public function release(string $domain): void
    {
        Http::get($this->baseUrl, [
            'ApiUser'    => $this->apiUser,
            'ApiKey'     => $this->apiKey,
            'UserName'   => $this->apiUser,
            'ClientIp'   => $this->clientIp,
            'Command'    => 'namecheap.domains.setRegistrarLock',
            'DomainName' => $domain,
            'LockAction' => 'UNLOCK',
        ]);
    }
}
