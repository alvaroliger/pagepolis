<?php

namespace App\Http\Controllers;

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

        $user      = auth()->user();
        $tier      = $user->isSubscribed() ? 'basic' : 'trial';
        $limits    = ['trial' => 5, 'basic' => 30, 'pro' => 100, 'admin' => 9999];
        $dailyLimit = $user->role === 'admin' ? 9999 : ($limits[$tier] ?? 5);

        // Resetear contador si es un nuevo día
        $today = now()->toDateString();
        if ($user->ai_calls_reset_date?->toDateString() !== $today) {
            $user->update(['ai_calls_today' => 0, 'ai_calls_reset_date' => $today]);
        }

        return Inertia::render('Editor/Index', [
            'project' => [
                'id'         => $project->id,
                'name'       => $project->name,
                'html'       => $project->html ?? '',
                'css'        => $project->css ?? '',
                'js'         => $project->js ?? '',
                'ai_history' => $project->ai_history ?? [],
                'seo_meta'   => $project->seo_meta ?? null,
                'status'     => $project->status,
            ],
            'aiUsage' => [
                'used'        => (int) $user->ai_calls_today,
                'limit'       => $dailyLimit,
                'tier'        => $tier,
                'isSubscribed'=> $user->isSubscribed(),
            ],
        ]);
    }

    public function save(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'html' => 'sometimes|string',
            'css'  => 'sometimes|string',
            'js'   => 'sometimes|string',
        ]);

        $project->update($request->only(['name', 'html', 'css', 'js']));

        return response()->json(['success' => true]);
    }
}
