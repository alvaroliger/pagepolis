<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

class ProjectController extends Controller
{
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
            ? '<script type="application/ld+json">' . json_encode($seo['schema']) . '</script>'
            : '';

        $css  = $project->css  ?? '';
        $body = $project->html ?? '';
        $js   = $project->js   ?? '';

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
