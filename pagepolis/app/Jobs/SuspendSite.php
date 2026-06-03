<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\NginxService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SuspendSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private Domain $domain) {}

    public function handle(NginxService $nginx): void
    {
        try {
            $sitesPath = config('app.sites_base_path', '/var/www/instaweb-sites');
            $projectId = $this->domain->project_id;

            $suspendedPage = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sitio suspendido</title>
<style>
body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #0f0f0f; color: #fff; }
.box { text-align: center; padding: 2rem; }
h1 { font-size: 2rem; color: #00FFD1; }
p { color: #aaa; margin: 1rem 0; }
a { color: #00FFD1; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>
<div class="box">
<h1>Este sitio está suspendido</h1>
<p>La suscripción asociada a este sitio ha sido cancelada o ha expirado.</p>
<p>Para reactivarlo, visita <a href="https://pagepolis.com">pagepolis.com</a></p>
</div>
</body>
</html>
HTML;

            $siteRoot = "{$sitesPath}/{$projectId}";
            if (!is_dir($siteRoot)) {
                mkdir($siteRoot, 0755, true);
            }

            file_put_contents("{$siteRoot}/index.html", $suspendedPage);

        } catch (\Exception $e) {
            Log::error('SuspendSite failed: ' . $e->getMessage());
        }
    }
}
