<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Models\Project;
use Illuminate\Http\Response;

class SiteController extends Controller
{
    /**
     * Sirve un sitio hospedado en pagepolis.com/s/{slug}
     */
    public function show(string $slug): Response
    {
        $project = Project::with('user')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Registrar visita de forma asíncrona (no bloquea la respuesta)
        defer(fn () => PageView::record($project->id));

        $seo    = $project->seo_meta ?? [];
        $title  = htmlspecialchars($seo['title']       ?? $project->name);
        $desc   = htmlspecialchars($seo['description'] ?? '');
        $ogTitle = htmlspecialchars($seo['og_title']       ?? $seo['title']       ?? $project->name);
        $ogDesc  = htmlspecialchars($seo['og_description'] ?? $seo['description'] ?? '');
        $schema = isset($seo['schema'])
            ? '<script type="application/ld+json">' . json_encode($seo['schema'], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) . '</script>'
            : '';

        $canonicalUrl = config('app.url') . '/s/' . $slug;
        $css  = $project->css  ?? '';
        $body = $project->html ?? '';
        $js   = $project->js   ?? '';

        // Open Graph / Twitter para compartir en redes
        $og = <<<OG
    <meta property="og:type" content="website">
    <meta property="og:title" content="{$ogTitle}">
    <meta property="og:description" content="{$ogDesc}">
    <meta property="og:url" content="{$canonicalUrl}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{$ogTitle}">
    <meta name="twitter:description" content="{$ogDesc}">
OG;

        // Badge "Hecho con Pagepolis": solo en sitios de usuarios SIN suscripción
        // (backlink + viralidad). Los planes de pago publican sin marca.
        $badge = '';
        if (!optional($project->user)->isSubscribed()) {
            $badge = <<<BADGE
<a href="https://pagepolis.com/?utm_source=badge&utm_medium=site&utm_campaign=free" target="_blank" rel="noopener" aria-label="Creado con Pagepolis" style="position:fixed;bottom:16px;right:16px;z-index:2147483647;display:inline-flex;align-items:center;gap:7px;padding:8px 14px;background:#0f172a;color:#fff;font:600 13px/1 system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;text-decoration:none;border-radius:9999px;box-shadow:0 6px 18px rgba(0,0,0,.28);"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z" fill="#a78bfa"/></svg>Hecho con Pagepolis</a>
BADGE;
        }

        // Captura de leads: intercepta el envío de CUALQUIER formulario de la web
        // generada y lo manda al endpoint (mismo origen, permitido por el CSP),
        // mostrando un "gracias" real. Antes el formulario no enviaba a ningún sitio.
        $leadEndpoint = "/s/{$slug}/lead";
        $leadCapture = <<<JS
<script>(function(){var EP="{$leadEndpoint}";document.addEventListener("submit",function(e){var f=e.target;if(!f||f.tagName!=="FORM")return;var d={},has=false;for(var i=0;i<f.elements.length;i++){var el=f.elements[i];if(el.name&&el.type!=="submit"&&el.type!=="button"){d[el.name]=el.value;if(el.value&&String(el.value).trim())has=true;}}if(!has)return;e.preventDefault();e.stopImmediatePropagation();var b=f.querySelector("[type=submit],button");if(b){b.disabled=true;}fetch(EP,{method:"POST",headers:{"Content-Type":"application/json","Accept":"application/json"},body:JSON.stringify({fields:d})}).then(function(r){return r.json().catch(function(){return{};});}).then(function(){f.innerHTML='<div style="padding:18px;border-radius:12px;background:#ecfdf5;color:#065f46;font:600 15px/1.45 system-ui,-apple-system,sans-serif;text-align:center">✅ ¡Gracias! Hemos recibido tu mensaje y te responderemos pronto.</div>';}).catch(function(){if(b){b.disabled=false;}alert("No se pudo enviar el mensaje. Inténtalo de nuevo.");});},true);})();</script>
JS;

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
{$og}
    {$schema}
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <style>{$css}</style>
</head>
<body>
{$body}
{$badge}
<script>{$js}</script>
{$leadCapture}
</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type'              => 'text/html; charset=UTF-8',
            'X-Content-Type-Options'    => 'nosniff',
            // Impide que scripts de sitios publicados accedan a cookies de pagepolis.com
            'Content-Security-Policy'   => "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: https:; connect-src 'self' https:;",
            'Cache-Control'             => 'public, max-age=300',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
        ]);
    }
}
