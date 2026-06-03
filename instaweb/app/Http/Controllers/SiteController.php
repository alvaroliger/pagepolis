<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Models\Project;
use Illuminate\Http\Response;

class SiteController extends Controller
{
    /**
     * Sirve un sitio hospedado en twtyg.com/s/{slug}
     */
    public function show(string $slug): Response
    {
        $project = Project::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Registrar visita de forma asíncrona (no bloquea la respuesta)
        defer(fn () => PageView::record($project->id));

        $seo    = $project->seo_meta ?? [];
        $title  = htmlspecialchars($seo['title']       ?? $project->name);
        $desc   = htmlspecialchars($seo['description'] ?? '');
        $schema = isset($seo['schema'])
            ? '<script type="application/ld+json">' . json_encode($seo['schema']) . '</script>'
            : '';

        $canonicalUrl = config('app.url') . '/s/' . $slug;
        $css  = $project->css  ?? '';
        $body = $project->html ?? '';
        $js   = $project->js   ?? '';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <meta name="description" content="{$desc}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{$canonicalUrl}">
    {$schema}
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <style>{$css}</style>
</head>
<body>
{$body}
<script>{$js}</script>
</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type'           => 'text/html',
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'public, max-age=300',
        ]);
    }
}
