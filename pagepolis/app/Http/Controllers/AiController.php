<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AiRateLimit;
use App\Jobs\GenerateWebsiteJob;
use App\Models\Project;
use App\Services\AnthropicService;
use App\Services\IntentRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    /**
     * Encola la generación de una web completa. La construcción real (2 fases,
     * varios minutos) corre en GenerateWebsiteJob; el editor sondea /ai/estado.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'prompt'     => 'required|string|max:2000',
            'project_id' => 'required|exists:projects,id',
            'images'     => 'nullable|array|max:4',
            'images.*'   => 'image|mimes:jpeg,png,webp,gif|max:8192',
        ]);

        $project = Project::where('id', $request->project_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($project->ai_status === 'generating') {
            return response()->json(['error' => 'Ya hay una generación en curso para esta web. Espera a que termine.'], 409);
        }

        $project->update(['ai_status' => 'generating', 'ai_progress' => 'En cola…']);
        GenerateWebsiteJob::dispatch($project->id, auth()->id(), 'generate', $request->prompt, $this->storeImages($request));

        return response()->json(['success' => true, 'status' => 'generating']);
    }

    /**
     * Aplica un cambio. Primero intenta resolverlo en LOCAL sin IA (gratis, al
     * instante) con el IntentRouter; solo si no lo reconoce, cae en la IA (que sí
     * cuenta cuota) en segundo plano.
     */
    public function update(Request $request, IntentRouter $router): JsonResponse
    {
        $request->validate([
            'instruction' => 'required|string|max:1000',
            'project_id'  => 'required|exists:projects,id',
            'images'      => 'nullable|array|max:4',
            'images.*'    => 'image|mimes:jpeg,png,webp,gif|max:8192',
        ]);

        $project = Project::where('id', $request->project_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($project->ai_status === 'generating') {
            return response()->json(['error' => 'Ya hay un cambio en curso para esta web. Espera a que termine.'], 409);
        }

        // 1) Intento LOCAL sin IA (gratis, instantáneo). Solo si no hay imágenes
        //    (las imágenes requieren visión de la IA).
        if (!$request->hasFile('images')) {
            $local = $router->handle($project, $request->instruction);
            if ($local !== null) {
                $project->update([
                    'html'       => $local['html'],
                    'css'        => $local['css'],
                    'js'         => $local['js'],
                    'ai_history' => array_merge($project->ai_history ?? [], [
                        ['role' => 'user', 'content' => $request->instruction, 'created_at' => now()->toISOString(), 'type' => 'update'],
                        ['role' => 'assistant', 'content' => $local['description'], 'created_at' => now()->toISOString(), 'type' => 'update', 'changed' => $local['changed'], 'instant' => true],
                    ]),
                ]);

                // Si cambió algo y está en dominio propio, refléjalo en internet.
                if (!empty($local['changed'])) {
                    $project->deployToLiveDomain();
                }

                return response()->json([
                    'success'     => true,
                    'status'      => 'done',          // resuelto al instante, sin sondeo
                    'instant'     => true,
                    'html'        => $local['html'],
                    'css'         => $local['css'],
                    'js'          => $local['js'],
                    'description' => $local['description'],
                    'changed'     => $local['changed'],
                ]);
            }
        }

        // 2) Respaldo con IA (cuenta cuota diaria). reserve() is atomic — no TOCTOU race.
        $user = auth()->user();
        if (!AiRateLimit::reserve($user)) {
            $limit = AiRateLimit::dailyLimit($user);
            return response()->json([
                'error'   => "Has alcanzado el límite de {$limit} generaciones con IA hoy. Se restablece " . now()->endOfDay()->diffForHumans() . '.',
                'upgrade' => AiRateLimit::tier($user) === 'trial',
            ], 429);
        }

        $project->update(['ai_status' => 'generating', 'ai_progress' => 'Aplicando tu cambio…']);
        GenerateWebsiteJob::dispatch($project->id, $user->id, 'update', $request->instruction, $this->storeImages($request));

        return response()->json(['success' => true, 'status' => 'generating']);
    }

    /**
     * Guarda las imágenes adjuntas en disco (storage/app/ai-uploads) y devuelve sus
     * rutas para que el job las lea. El job las borra al terminar.
     *
     * @return array<int,string>
     */
    private function storeImages(Request $request): array
    {
        $paths = [];
        foreach ((array) $request->file('images', []) as $file) {
            if ($file && $file->isValid()) {
                $paths[] = $file->store('ai-uploads', 'local');
            }
        }
        return $paths;
    }

    /**
     * Estado de la generación en curso (sondeo desde el editor). Cuando termina,
     * devuelve el HTML/CSS/JS resultante y la descripción del asistente.
     */
    public function status(Project $project): JsonResponse
    {
        abort_unless($project->user_id === auth()->id(), 403);

        $status = $project->ai_status ?: 'ready';

        // Salvavidas: si lleva "generando" demasiado tiempo (worker caído, job
        // perdido…), márcalo como error para no dejar al usuario sondeando sin fin.
        if ($status === 'generating' && $project->updated_at && $project->updated_at->lt(now()->subMinutes(15))) {
            $project->update(['ai_status' => 'error', 'ai_progress' => 'La generación tardó demasiado. Vuelve a intentarlo.']);
            $status = 'error';
        }

        $payload = ['status' => $status, 'progress' => $project->ai_progress];

        if ($status === 'ready') {
            $last = collect($project->ai_history ?? [])
                ->where('role', 'assistant')
                ->last();

            $payload += [
                'html'        => $project->html ?? '',
                'css'         => $project->css ?? '',
                'js'          => $project->js ?? '',
                'description' => $last['content'] ?? 'Listo.',
                'changed'     => $last['changed'] ?? [],
                'type'        => $last['type'] ?? 'generate',
            ];
        } elseif ($status === 'error') {
            $payload['error'] = $project->ai_progress ?: 'No se pudo completar. Inténtalo de nuevo.';
        }

        return response()->json($payload);
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
