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
            $result = $ai->generateWebsite($request->prompt);

            $project->update([
                'html'       => $result['html'],
                'css'        => $result['css'],
                'js'         => $result['js'] ?? '',
                'ai_history' => array_merge($project->ai_history ?? [], [[
                    'role'       => 'user',
                    'content'    => $request->prompt,
                    'created_at' => now()->toISOString(),
                    'type'       => 'generate',
                ], [
                    'role'       => 'assistant',
                    'content'    => $result['description'] ?? 'Web generada correctamente.',
                    'created_at' => now()->toISOString(),
                    'type'       => 'generate',
                ]]),
            ]);

            return response()->json([
                'success'     => true,
                'html'        => $result['html'],
                'css'         => $result['css'],
                'js'          => $result['js'] ?? '',
                'description' => $result['description'] ?? 'Web generada.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Aplica un cambio quirúrgico sin regenerar la web completa.
     */
    public function update(Request $request, AnthropicService $ai): JsonResponse
    {
        $request->validate([
            'instruction' => 'required|string|max:1000',
            'project_id'  => 'required|exists:projects,id',
        ]);

        $project = Project::where('id', $request->project_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        try {
            // Pasar las últimas 6 entradas del historial como contexto de conversación
            $recentHistory = collect($project->ai_history ?? [])
                ->filter(fn($e) => ($e['type'] ?? '') === 'update')
                ->takeLast(6)
                ->map(fn($e) => ['role' => $e['role'], 'content' => $e['content']])
                ->values()
                ->all();

            $result = $ai->updateWebsite(
                $request->instruction,
                $project->html ?? '',
                $project->css  ?? '',
                $project->js   ?? '',
                $recentHistory
            );

            $newHtml = $result['html'] ?? $project->html;
            $newCss  = $result['css']  ?? $project->css;
            $newJs   = $result['js']   ?? $project->js;

            $project->update([
                'html'       => $newHtml,
                'css'        => $newCss,
                'js'         => $newJs,
                'ai_history' => array_merge($project->ai_history ?? [], [[
                    'role'       => 'user',
                    'content'    => $request->instruction,
                    'created_at' => now()->toISOString(),
                    'type'       => 'update',
                ], [
                    'role'       => 'assistant',
                    'content'    => $result['description'] ?? 'Cambio aplicado.',
                    'created_at' => now()->toISOString(),
                    'type'       => 'update',
                    'changed'    => $result['changed'] ?? [],
                ]]),
            ]);

            return response()->json([
                'success'     => true,
                'html'        => $newHtml,
                'css'         => $newCss,
                'js'          => $newJs,
                'description' => $result['description'] ?? 'Cambio aplicado.',
                'changed'     => $result['changed'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Genera y guarda metadatos SEO para el proyecto.
     */
    public function seo(Request $request, AnthropicService $ai): JsonResponse
    {
        $request->validate([
            'project_id'    => 'required|exists:projects,id',
            'business_hint' => 'nullable|string|max:200',
        ]);

        $project = Project::where('id', $request->project_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        try {
            $meta = $ai->generateSeoMeta($project->html ?? '', $request->business_hint ?? '');

            // Guardar los metadatos en el proyecto
            $project->update(['seo_meta' => $meta]);

            return response()->json(['success' => true, 'meta' => $meta]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
