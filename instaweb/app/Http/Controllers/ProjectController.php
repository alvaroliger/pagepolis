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

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$project->name}</title>
            <style>{$project->css}</style>
        </head>
        <body>
            {$project->html}
            <script>{$project->js}</script>
        </body>
        </html>
        HTML;

        return response($html)->header('Content-Type', 'text/html');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        $project->delete();

        return redirect()->route('dashboard');
    }
}
