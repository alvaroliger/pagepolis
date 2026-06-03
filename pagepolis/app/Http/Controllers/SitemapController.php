<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = config('app.url');

        // Sitios publicados con dominio activo
        $sites = Domain::where('status', 'active')
            ->with('project')
            ->get()
            ->filter(fn($d) => $d->project?->status === 'published');

        $urls = collect([
            ['loc' => $baseUrl . '/',           'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $baseUrl . '/register',   'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => $baseUrl . '/plantillas', 'priority' => '0.8', 'changefreq' => 'weekly'],
        ]);

        // Añadir los subdominios de pagepolis.com si existen
        foreach ($sites->where('type', 'subdomain') as $domain) {
            $urls->push([
                'loc'        => 'https://' . $domain->domain,
                'priority'   => '0.5',
                'changefreq' => 'weekly',
                'lastmod'    => $domain->project->updated_at->toAtomString(),
            ]);
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $baseUrl = config('app.url');

        $content = <<<ROBOTS
User-agent: *
Allow: /
Disallow: /dashboard
Disallow: /editor
Disallow: /publicar
Disallow: /perfil
Disallow: /admin
Disallow: /facturacion

Sitemap: {$baseUrl}/sitemap.xml
ROBOTS;

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
