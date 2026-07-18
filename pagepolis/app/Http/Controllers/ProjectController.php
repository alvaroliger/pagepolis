<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AiRateLimit;
use App\Jobs\GenerateWebsiteJob;
use App\Models\Project;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProjectController extends Controller
{
    /**
     * Asistente de creación con IA: muestra el mismo aviso de cuota diaria que ya
     * ve el cliente en el editor, para que si ya no le quedan generaciones hoy lo
     * sepa antes de rellenar el formulario en vez de descubrirlo al enviarlo.
     */
    public function create(): InertiaResponse
    {
        $user = auth()->user();

        return Inertia::render('Create/Index', [
            'aiUsage' => [
                'used'         => AiRateLimit::used($user),
                'limit'        => AiRateLimit::dailyLimit($user),
                'tier'         => AiRateLimit::tier($user),
                'isSubscribed' => $user->isSubscribed(),
            ],
        ]);
    }

    /**
     * Asistente "a prueba de abuelos": con 4 respuestas en lenguaje natural crea el
     * proyecto y lanza la generación de la web en segundo plano. El editor (al que
     * se redirige) muestra el progreso y aplica el resultado al terminar.
     */
    public function createWithAi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'business_name' => 'required|string|max:100',
            'description'   => 'required|string|max:1000',
            'sells'         => 'boolean',
            'whatsapp'      => 'nullable|string|max:30',
            'style'         => 'nullable|string|max:40',
            'location'      => 'nullable|string|max:80',
        ]);

        $user = auth()->user();

        // Límite anti-abuso del tier gratuito (los de pago no tienen tope).
        if (!$user->isSubscribed()) {
            $max = config('pagepolis.limits.free_max_projects', 10);
            if ($user->projects()->count() >= $max) {
                return response()->json([
                    'error' => "El plan gratuito permite hasta {$max} proyectos. Mejora a un plan de pago para crear más.",
                ], 422);
            }
        }

        $project = Project::create([
            'user_id'     => $user->id,
            'name'        => $data['business_name'],
            'status'      => 'draft',
            'ai_status'   => 'generating',
            'ai_progress' => 'En cola…',
        ]);

        GenerateWebsiteJob::dispatch($project->id, $user->id, 'generate', $this->buildPrompt($data));

        return response()->json([
            'success'    => true,
            'project_id' => $project->id,
            'redirect'   => route('editor.index', $project->id),
        ]);
    }

    /**
     * Convierte las respuestas del asistente en un encargo claro para la IA.
     */
    private function buildPrompt(array $d): string
    {
        $p = "Crea la web profesional y completa del negocio «{$d['business_name']}».\n";
        $p .= "A qué se dedica: {$d['description']}.\n";

        if (!empty($d['location'])) {
            $p .= "Ubicación: {$d['location']}.\n";
        }
        if (!empty($d['sells'])) {
            $p .= "IMPORTANTE: el negocio VENDE PRODUCTOS ONLINE. Incluye una tienda completa con catálogo de productos, carrito y checkout.";
            if (!empty($d['whatsapp'])) {
                $p .= " Recibe los pedidos por WhatsApp en el número {$d['whatsapp']}.";
            }
            $p .= "\n";
        }
        if (!empty($d['style'])) {
            $p .= "Estilo visual preferido: {$d['style']}.\n";
        }

        return trim($p);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'template_id' => 'nullable|exists:templates,id',
        ]);

        $user = auth()->user();

        // Límite anti-abuso para el tier gratuito (los de pago no tienen tope).
        if (!$user->isSubscribed()) {
            $max = config('pagepolis.limits.free_max_projects', 10);
            if ($user->projects()->count() >= $max) {
                return redirect()->back()->with('error',
                    "El plan gratuito permite hasta {$max} proyectos. Mejora a un plan de pago para crear más.");
            }
        }

        $template = $request->template_id ? Template::find($request->template_id) : null;

        $project = Project::create([
            'user_id'     => auth()->id(),
            'name'        => $request->name,
            'template_id' => $request->template_id,
            'html'        => $template?->html ?? '',
            'css'         => $template?->css ?? '',
            'js'          => $template?->js ?? '',
            'status'      => 'draft',
        ]);

        if ($template) {
            $template->increment('uses_count');
        }

        return redirect()->route('editor.index', $project->id);
    }

    public function preview(Project $project): HttpResponse
    {
        $this->authorize('view', $project);

        $seo   = $project->seo_meta ?? [];
        $title = htmlspecialchars($seo['title'] ?? $project->name);
        $desc  = htmlspecialchars($seo['description'] ?? '');
        $schema = isset($seo['schema'])
            ? '<script type="application/ld+json">' . json_encode($seo['schema'], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) . '</script>'
            : '';

        $css  = $project->css  ?? '';
        $body = $project->html ?? '';
        $js   = $project->js   ?? '';

        // Guard de la vista previa: evita que al pulsar enlaces el iframe navegue a
        // la app (lo que saturaba el servidor local → "localhost rechaza la conexión").
        $previewGuard = "(function(){document.addEventListener('click',function(e){var a=e.target&&e.target.closest&&e.target.closest('a');if(!a)return;var h=a.getAttribute('href')||'';if(h.charAt(0)==='#'){e.preventDefault();try{if(h.length>1){var el=document.querySelector(h);if(el)el.scrollIntoView({behavior:'smooth'});}}catch(x){}}else{e.preventDefault();}},true);document.addEventListener('submit',function(e){e.preventDefault();},true);})();";

        // La preview se sirve en un iframe sandboxed desde el editor.
        // Los headers CSP impiden acceso a cookies de pagepolis.com.
        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <meta name="description" content="{$desc}">
    <meta name="robots" content="noindex, nofollow">
    {$schema}
    <style>{$css}</style>
</head>
<body>
{$body}
<script>{$previewGuard}</script>
<script>{$js}</script>
</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type'              => 'text/html; charset=UTF-8',
            'Content-Security-Policy'   => "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: https:; script-src 'unsafe-inline' 'unsafe-eval' https:; connect-src 'none';",
            'X-Content-Type-Options'    => 'nosniff',
            'Cache-Control'             => 'no-store',
        ]);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return redirect()->route('dashboard');
    }

    /**
     * Restaura un proyecto enviado a la papelera (soft delete).
     */
    public function restore(int $id): RedirectResponse
    {
        $project = Project::withTrashed()->findOrFail($id);
        $this->authorize('delete', $project);
        $project->restore();

        return redirect()->route('dashboard');
    }

    /**
     * Elimina un proyecto de forma permanente desde la papelera.
     */
    public function forceDestroy(int $id): RedirectResponse
    {
        $project = Project::withTrashed()->findOrFail($id);
        $this->authorize('delete', $project);

        // Si estaba publicado en un dominio/subdominio, libera el vhost.
        $project->domain()->delete();
        $project->forceDelete();

        return redirect()->route('dashboard');
    }

    /**
     * Descarga la web del proyecto como archivo ZIP (HTML/CSS/JS).
     */
    public function exportZip(Project $project): HttpResponse
    {
        $this->authorize('view', $project);

        $title = htmlspecialchars($project->name);
        $css   = trim($project->css ?? '');
        $js    = trim($project->js ?? '');
        $body  = $project->html ?? '';

        $cssLink = $css ? '    <link rel="stylesheet" href="styles.css">' : '';
        $jsTag   = $js  ? '<script src="script.js"></script>' : '';

        $indexHtml = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
{$cssLink}
</head>
<body>
{$body}
{$jsTag}
</body>
</html>
HTML;

        $slug = $project->slug ?: ('pagepolis-' . $project->id);
        $tmp  = tempnam(sys_get_temp_dir(), 'ppzip');

        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('index.html', $indexHtml);
        if ($css) {
            $zip->addFromString('styles.css', $css);
        }
        if ($js) {
            $zip->addFromString('script.js', $js);
        }
        $zip->addFromString('LEEME.txt', "Web exportada desde Pagepolis (https://pagepolis.com)\nProyecto: {$project->name}\n");
        $zip->close();

        $contents = file_get_contents($tmp);
        @unlink($tmp);

        return response($contents, 200, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $slug . '.zip"',
            'Content-Length'      => (string) strlen($contents),
        ]);
    }
}
