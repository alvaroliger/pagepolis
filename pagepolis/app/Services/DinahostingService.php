<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DinahostingService
{
    private string $apiUrl  = 'https://panel.dinahosting.com/api';
    private string $user;
    private string $pass;

    public function __construct()
    {
        $this->user = config('services.dinahosting.user');
        $this->pass = config('services.dinahosting.pass');
    }

    public function checkAvailability(string $domain): array
    {
        $response = $this->call('domain_check', ['domain' => $domain]);

        $available = isset($response['result']) && $response['result'] === 'available';
        $price     = (float)($response['price'] ?? 12.00);

        return ['available' => $available, 'price' => $price];
    }

    public function register(string $domain, array $contact): string
    {
        $response = $this->call('domain_register', [
            'domain'    => $domain,
            'years'     => 1,
            'firstname' => $contact['firstname'] ?? '',
            'lastname'  => $contact['lastname']  ?? '',
            'email'     => $contact['email']     ?? '',
            'address'   => $contact['address']   ?? '',
            'city'      => $contact['city']      ?? '',
            'zip'       => $contact['zip']        ?? '',
            'country'   => $contact['country']   ?? 'ES',
            'phone'     => $contact['phone']     ?? '',
        ]);

        if (empty($response['order_id'])) {
            throw new \Exception('Dinahosting: registro fallido para ' . $domain);
        }

        return (string) $response['order_id'];
    }

    public function release(string $domain): void
    {
        // Marcar el dominio para no-renovación en Dinahosting
        $this->call('domain_disable_autorenew', ['domain' => $domain]);
    }

    private function call(string $command, array $params = []): array
    {
        $response = Http::withBasicAuth($this->user, $this->pass)
            ->timeout(30)
            ->post("{$this->apiUrl}/{$command}", $params);

        if ($response->failed()) {
            throw new \Exception("Dinahosting API error ({$command}): " . $response->status());
        }

        return $response->json() ?? [];
    }
}
