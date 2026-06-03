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
