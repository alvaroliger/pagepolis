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
            $result = $ai->forUser(auth()->user())->generateWebsite($request->prompt);

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

            $result = $ai->forUser(auth()->user())->updateWebsite(
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

            // Validar estructura antes de guardar
            $clean = [
                'title'          => isset($meta['title'])          ? substr((string) $meta['title'],          0, 60)  : null,
                'description'    => isset($meta['description'])    ? substr((string) $meta['description'],    0, 155) : null,
                'keywords'       => isset($meta['keywords'])       ? substr((string) $meta['keywords'],       0, 200) : null,
                'og_title'       => isset($meta['og_title'])       ? substr((string) $meta['og_title'],       0, 70)  : null,
                'og_description' => isset($meta['og_description']) ? substr((string) $meta['og_description'], 0, 200) : null,
                'schema'         => isset($meta['schema']) && is_array($meta['schema']) ? $meta['schema'] : null,
            ];

            $project->update(['seo_meta' => array_filter($clean)]);

            return response()->json(['success' => true, 'meta' => $clean]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
