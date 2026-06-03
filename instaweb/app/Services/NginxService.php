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

    public function deploySite(int $projectId, string $html, string $css, string $js, array $seoMeta = []): void
    {
        $siteRoot = "{$this->sitesPath}/{$projectId}";

        if (!is_dir($siteRoot)) {
            mkdir($siteRoot, 0755, true);
        }

        $fullHtml = $this->buildFullHtml($html, $css, $js, $seoMeta, $projectId);
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

    private function buildFullHtml(string $html, string $css, string $js, array $seo = [], int $projectId = 0): string
    {
        $title       = htmlspecialchars($seo['title']       ?? 'Mi web');
        $description = htmlspecialchars($seo['description'] ?? '');
        $keywords    = htmlspecialchars($seo['keywords']    ?? '');
        $ogTitle     = htmlspecialchars($seo['og_title']    ?? $title);
        $ogDesc      = htmlspecialchars($seo['og_description'] ?? $description);
        $schema      = isset($seo['schema']) ? json_encode($seo['schema']) : '';

        $seoTags = <<<META
    <title>{$title}</title>
    <meta name="description" content="{$description}">
    <meta name="keywords" content="{$keywords}">
    <meta property="og:title" content="{$ogTitle}">
    <meta property="og:description" content="{$ogDesc}">
    <meta property="og:type" content="website">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#ffffff">
    <link rel="canonical" href="">
META;

        if ($schema) {
            $seoTags .= "\n    <script type=\"application/ld+json\">{$schema}</script>";
        }

        // Pixel de tracking (para sitios con dominio propio)
        $trackingBase  = config('app.url');
        $trackingPixel = $projectId
            ? "<img src=\"{$trackingBase}/t/{$projectId}\" width=\"1\" height=\"1\" alt=\"\" style=\"position:absolute;\">"
            : '';

        // Headers de seguridad via meta equivalentes
        $securityMeta = <<<SEC
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Permissions-Policy" content="camera=(), microphone=(), geolocation=()">
SEC;

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
{$seoTags}
{$securityMeta}
    <style>{$css}</style>
</head>
<body>
{$html}
<script>{$js}</script>
{$trackingPixel}
</body>
</html>
HTML;
    }

    private function reload(): void
    {
        exec('sudo nginx -t && sudo systemctl reload nginx');
    }
}
