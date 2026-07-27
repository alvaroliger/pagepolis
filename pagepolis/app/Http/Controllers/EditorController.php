<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AiRateLimit;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EditorController extends Controller
{
    public function index(Project $project): Response
    {
        $this->authorize('view', $project);

        $user = auth()->user();
        $project->loadMissing('domain');

        return Inertia::render('Editor/Index', [
            'project' => [
                'id'         => $project->id,
                'name'       => $project->name,
                'html'       => $project->html ?? '',
                'css'        => $project->css ?? '',
                'js'         => $project->js ?? '',
                'ai_history' => $project->ai_history ?? [],
                'can_undo'   => $project->canUndoAiChange(),
                'seo_meta'   => $project->seo_meta ?? null,
                'status'     => $project->status,
                'ai_status'  => $project->ai_status,
                'ai_progress'=> $project->ai_progress,
                'live_url'   => match ($project->domain?->type) {
                    'custom'    => 'https://' . $project->domain->domain,
                    'subdomain' => 'https://' . $project->domain->domain,
                    'path'      => config('app.url') . '/s/' . $project->slug,
                    default     => null,
                },
            ],
            'aiUsage' => [
                'used'        => AiRateLimit::used($user),
                'limit'       => AiRateLimit::dailyLimit($user),
                'tier'        => AiRateLimit::tier($user),
                'isSubscribed'=> $user->isSubscribed(),
            ],
        ]);
    }

    public function save(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $request->validate([
            'name' => 'sometimes|nullable|string|max:100',
            'html' => 'sometimes|nullable|string|max:2097152',
            'css'  => 'sometimes|nullable|string|max:524288',
            'js'   => 'sometimes|nullable|string|max:524288',
        ]);

        // ConvertEmptyStringsToNull convierte un campo vacío ("") en null antes de
        // llegar aquí: si un cliente borra todo el HTML/CSS/JS en el editor avanzado
        // y guarda, debe persistirse como cadena vacía (no null, que el resto del
        // código no espera). El nombre nunca puede quedar vacío en BD, así que un
        // nombre vacío se ignora y se conserva el anterior en vez de romper el guardado.
        $data = $request->only(['name', 'html', 'css', 'js']);
        foreach (['html', 'css', 'js'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === null) {
                $data[$field] = '';
            }
        }
        if (array_key_exists('name', $data) && $data['name'] === null) {
            unset($data['name']);
        }

        $project->update($data);

        // Si está en un dominio propio activo, refleja los cambios en internet.
        $project->deployToLiveDomain();

        return response()->json(['success' => true]);
    }
}
