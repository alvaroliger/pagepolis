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

        return Inertia::render('Editor/Index', [
            'project' => [
                'id'         => $project->id,
                'name'       => $project->name,
                'html'       => $project->html ?? '',
                'css'        => $project->css ?? '',
                'js'         => $project->js ?? '',
                'ai_history' => $project->ai_history ?? [],
                'seo_meta'   => $project->seo_meta ?? null,
                'preview_url'=> $project->preview_url,
                'status'     => $project->status,
                'ai_status'  => $project->ai_status,
                'ai_progress'=> $project->ai_progress,
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
            'name' => 'sometimes|string|max:100',
            'html' => 'sometimes|string|max:2097152',
            'css'  => 'sometimes|string|max:524288',
            'js'   => 'sometimes|string|max:524288',
        ]);

        $project->update($request->only(['name', 'html', 'css', 'js']));

        // Si está en un dominio propio activo, refleja los cambios en internet.
        $project->deployToLiveDomain();

        return response()->json(['success' => true]);
    }

    /**
     * Guarda ediciones manuales del título/descripción/keywords SEO (sin pasar
     * por la IA). Conserva og_title/og_description/schema, que solo genera la IA.
     */
    public function updateSeo(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'title'       => 'nullable|string|max:60',
            'description' => 'nullable|string|max:155',
            'keywords'    => 'nullable|string|max:200',
        ]);

        $meta = array_filter(
            array_merge($project->seo_meta ?? [], $validated),
            fn ($value) => $value !== null && $value !== ''
        );
        $project->update(['seo_meta' => $meta]);

        return response()->json(['success' => true, 'meta' => $meta]);
    }
}
