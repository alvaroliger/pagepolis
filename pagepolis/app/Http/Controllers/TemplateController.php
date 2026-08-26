<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\TemplateEngine;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    /**
     * Evita que al pulsar enlaces dentro de una vista previa el iframe navegue
     * a la app (en local eso saturaba el servidor). Los anclas internas siguen
     * funcionando; el resto de navegación y los envíos de formulario se anulan.
     */
    private const PREVIEW_GUARD = '<script>(function(){document.addEventListener("click",function(e){var a=e.target&&e.target.closest&&e.target.closest("a");if(!a)return;var h=a.getAttribute("href")||"";if(h.charAt(0)==="#"){e.preventDefault();try{if(h.length>1){var el=document.querySelector(h);if(el)el.scrollIntoView({behavior:"smooth"});}}catch(x){}}else{e.preventDefault();}},true);document.addEventListener("submit",function(e){e.preventDefault();},true);})();</script>';

    /**
     * Galería. NO viaja el código de las plantillas.
     *
     * POR QUÉ. Antes cada fila llevaba su html/css/js y la página montaba un
     * iframe por plantilla para enseñar la miniatura "viva". Medido con 218
     * plantillas activas: 2,95 MB solo de documento Inertia, 38 MB de red
     * (cada iframe descargaba además las fotos y vídeos remotos de su propia
     * plantilla), 299 documentos, 91.873 nodos y 12,5 s de CPU en el hilo
     * principal. Los iframes del mismo origen NO obtienen proceso propio: los
     * 218 documentos competían por el único hilo principal del renderizador,
     * así que ni escalonar el montaje ni content-visibility lo arreglaban —
     * repartían el trabajo, pero el trabajo total seguía siendo el mismo.
     *
     * Ahora la rejilla enseña una imagen pre-generada por plantilla
     * (`php artisan templates:thumbnails`). El navegador la descarga en
     * paralelo, la descodifica FUERA del hilo principal y la lazy-carga de
     * forma nativa: cero documentos extra. El código completo solo se pide al
     * abrir la vista previa, de una en una.
     */
    public function index(): Response
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->select('id', 'name', 'category', 'thumbnail', 'preview_video', 'tags', 'is_premium', 'uses_count')
            // Se calcula en SQL para no tener que traerse el html (1,47 MB en
            // total) solo para saber si la plantilla lleva hero 3D. Se detecta por
            // los marcadores del motor (data-hero3d/data-k3d) Y por la presencia de
            // un <canvas> (WebGL): 6 plantillas con hero 3D real por canvas —Nova,
            // Atlas, Sentinel, Vidrio, Órbita, Hiperspacio— no llevaban esos
            // marcadores y salían mal, en el grupo de "clásicas".
            ->selectRaw("(CASE WHEN html LIKE '%data-hero3d%' OR html LIKE '%data-k3d%' OR html LIKE '%<canvas%' THEN 1 ELSE 0 END) AS is_3d")
            ->orderByDesc('uses_count')
            ->get()
            ->map(fn (Template $t) => [
                'id'            => $t->id,
                'name'          => $t->name,
                'category'      => $t->category,
                'thumbnail'     => $t->thumbnail,
                'preview_video' => $t->preview_video,
                'tags'          => $t->tags,
                'is_premium' => (bool) $t->is_premium,
                'uses_count' => $t->uses_count,
                'has_3d'     => (bool) $t->is_3d,
            ]);

        return Inertia::render('Templates/Index', [
            'templates'  => $templates,
            'categories' => $templates->pluck('category')->unique()->values(),
        ]);
    }

    /**
     * La plantilla como página independiente, montada igual que un sitio
     * publicado (motor enlazado + CSS/JS propios). La consume el iframe de la
     * vista previa y el generador de miniaturas, así que lo que se ve en la
     * galería es exactamente lo que sale publicado.
     */
    public function render(Template $template): HttpResponse
    {
        $engineTags = TemplateEngine::tags($template->engine ?? null);
        [$fontLinks, $ownCss] = TemplateEngine::extractFontImports($template->css ?? '');

        $title = e($template->name);
        $body  = $template->html ?? '';
        $js    = $template->js ?? '';
        $guard = self::PREVIEW_GUARD;

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <meta name="robots" content="noindex, nofollow">
            {$engineTags['css']}
            {$fontLinks}
            <style>{$ownCss}</style>
        </head>
        <body>
        {$body}
        {$engineTags['js']}
        <script>{$js}</script>
        {$guard}
        </body>
        </html>
        HTML;

        return response($html, 200, [
            'Content-Type'           => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag'           => 'noindex, nofollow',
            'Cache-Control'          => 'public, max-age=300',
        ]);
    }
}
