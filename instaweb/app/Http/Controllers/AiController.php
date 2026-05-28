<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\AnthropicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function generate(Request $request, AnthropicService $ai): JsonResponse
    {
        $request->validate([
            'prompt'     => 'required|string|max:2000',
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::where('id', $request->project_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        try {
            foreach ($ai->generateWebsite($request->prompt) as $result) {
                $project->update([
                    'html'       => $result['html'],
                    'css'        => $result['css'],
                    'js'         => $result['js'] ?? '',
                    'ai_history' => array_merge($project->ai_history ?? [], [[
                        'prompt'     => $request->prompt,
                        'created_at' => now()->toISOString(),
                    ]]),
                ]);

                return response()->json([
                    'success' => true,
                    'html'    => $result['html'],
                    'css'     => $result['css'],
                    'js'      => $result['js'] ?? '',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['error' => 'Sin resultado'], 500);
    }
}
