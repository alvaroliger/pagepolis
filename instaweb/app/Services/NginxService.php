<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class NginxService
{
    private string $sitesPath;
    private string $nginxAvailable = '/etc/nginx/sites-available';
    private string $nginxEnabled   = '/etc/nginx/sites-enabled';

    public function __construct()
    {
        $this->sitesPath = config('app.sites_base_path', '/var/www/twtyg-sites');
    }

    public function createVHost(string $domain, int $projectId): void
    {
        $siteRoot = "{$this->sitesPath}/{$projectId}";
        $config   = $this->buildVHostConfig($domain, $siteRoot);
        $confFile = "{$this->nginxAvailable}/{$domain}.conf";

        file_put_contents($confFile, $config);

        if (!file_exists("{$this->nginxEnabled}/{$domain}.conf")) {
            symlink($confFile, "{$this->nginxEnabled}/{$domain}.conf");
        }

        $this->reload();
    }

    public function removeVHost(string $domain): void
    {
        $enabledConf  = "{$this->nginxEnabled}/{$domain}.conf";
        $availableConf = "{$this->nginxAvailable}/{$domain}.conf";

        if (file_exists($enabledConf)) {
            unlink($enabledConf);
        }

        if (file_exists($availableConf)) {
            unlink($availableConf);
        }

        $this->reload();
    }

    public function deploySite(int $projectId, string $html, string $css, string $js): void
    {
        $siteRoot = "{$this->sitesPath}/{$projectId}";

        if (!is_dir($siteRoot)) {
            mkdir($siteRoot, 0755, true);
        }

        $fullHtml = $this->buildFullHtml($html, $css, $js);
        file_put_contents("{$siteRoot}/index.html", $fullHtml);
    }

    private function buildVHostConfig(string $domain, string $root): string
    {
        return <<<NGINX
server {
    listen 80;
    server_name {$domain};
    root {$root};

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location ~ /\\. { deny all; }
}
NGINX;
    }

    private function buildFullHtml(string $html, string $css, string $js): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>{$css}</style>
</head>
<body>
{$html}
<script>{$js}</script>
</body>
</html>
HTML;
    }

    private function reload(): void
    {
        exec('sudo nginx -t && sudo systemctl reload nginx');
    }
}
