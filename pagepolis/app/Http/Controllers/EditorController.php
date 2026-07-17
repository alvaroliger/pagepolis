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
                'can_undo'   => $project->canUndoAiChange(),
                'seo_meta'   => $project->seo_meta ?? null,
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
}
