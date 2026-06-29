<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Dinahosting API client (real API).
 *
 * Endpoint:  https://dinahosting.com/special/api.php
 * Auth:      AUTH_USER / AUTH_PWD (the dinahosting account credentials)
 * Format:    responseType=Json; success when responseCode === 1000.
 *
 * Used by Pagepolis to (1) check a domain before letting a client reserve it and
 * (2) auto-register the chosen domain on the owner's dinahosting balance once the
 * client has paid (see App\Jobs\PurchaseDomain).
 *
 * Docs: https://en.dinahosting.com/api/documentation
 */
class DinahostingService
{
    private string $apiUrl = 'https://dinahosting.com/special/api.php';
    private string $user;
    private string $pass;

    public function __construct()
    {
        $this->user = (string) config('services.dinahosting.user');
        $this->pass = (string) config('services.dinahosting.pass');
    }

    /**
     * Is the domain available to register?  command: Domain_CheckForRegister
     * Returns ['available' => bool, 'price' => float].
     */
    public function checkAvailability(string $domain): array
    {
        $domain   = strtolower(trim($domain));
        $response = $this->call('Domain_CheckForRegister', ['domain' => $domain]);

        // The API returns data:true (free) / data:false (taken).
        $available = ($response['data'] ?? false) === true;
        $price     = (float) config('services.dinahosting.default_price', 12.00);

        return ['available' => $available, 'price' => $price];
    }

    /**
     * Buy & register a domain on the account balance.  command: Billing_Buy_Domain
     * Charges the owner's dinahosting "saldo". Returns the transaction id.
     *
     * NOTE: this is a REAL purchase (dinahosting has no full simulate mode for
     * this command). Only call it after the client has paid.
     */
    public function register(string $domain, array $contact): string
    {
        $domain   = strtolower(trim($domain));
        $contacts = $this->buildContacts($contact);

        $response = $this->call('Billing_Buy_Domain', [
            'domain'            => $domain,
            'period'            => 1,
            'contacts'          => $contacts,
            'paymentMethodType' => 'saldo',
            'whoisProtection'   => 'true',
        ]);

        // Billing_Buy_Domain has a void payload; success is responseCode 1000.
        return (string) ($response['trId'] ?? $domain);
    }

    /**
     * Stop a domain from auto-renewing (so it lapses at period end).
     */
    public function release(string $domain): void
    {
        $this->call('Billing_Autorenew_SetOff', ['domain' => strtolower(trim($domain))]);
    }

    /**
     * Flatten a single contact into the role-prefixed comma-serialised "contacts"
     * struct the API expects (same data for Registrant/Admin/Tech/Billing roles).
     */
    private function buildContacts(array $c): string
    {
        $fields = [
            'FirstName'     => $c['firstname'] ?? '',
            'LastName'      => $c['lastname']  ?? '',
            'Address1'      => $c['address']   ?? '',
            'City'          => $c['city']      ?? '',
            'StateProvince' => $c['state']     ?? ($c['city'] ?? ''),
            'PostalCode'    => $c['zip']        ?? '',
            'Country'       => $c['country']   ?? 'ES',
            'Phone'         => $c['phone']     ?? '',
            'EmailAddress'  => $c['email']     ?? '',
        ];

        $pairs = [];
        foreach (['Registrant', 'Admin', 'Tech', 'Billing'] as $role) {
            foreach ($fields as $name => $value) {
                // Commas are the struct separator, so strip them from values.
                $pairs[] = $role . $name;
                $pairs[] = str_replace(',', ' ', (string) $value);
            }
        }

        return implode(',', $pairs);
    }

    /**
     * Low-level API call. Throws on transport error or a non-success responseCode.
     */
    private function call(string $command, array $params = []): array
    {
        $response = Http::asForm()->timeout(30)->post($this->apiUrl, array_merge([
            'AUTH_USER'    => $this->user,
            'AUTH_PWD'     => $this->pass,
            'responseType' => 'Json',
            'command'      => $command,
        ], $params));

        if ($response->failed()) {
            throw new \Exception("Dinahosting transport error ({$command}): HTTP " . $response->status());
        }

        $json = $response->json() ?? [];

        // 1000 = success per the dinahosting API.
        if ((int) ($json['responseCode'] ?? 0) !== 1000) {
            $msg = $json['message'] ?? 'unknown error';
            if (!empty($json['errors'][0]['message'])) {
                $msg = $json['errors'][0]['message'];
            }
            throw new \Exception("Dinahosting API error ({$command}): {$msg}");
        }

        return $json;
    }
}
