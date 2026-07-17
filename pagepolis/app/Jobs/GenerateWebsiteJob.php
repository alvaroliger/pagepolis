<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Services\AnthropicService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Genera o actualiza una web con IA en segundo plano. La generación de un sitio
 * completo tarda varios minutos (2 fases: HTML/CSS y luego JS), así que NO puede
 * ir en el ciclo HTTP (timeouts de nginx/php-fpm). El editor sondea el estado.
 */
class GenerateWebsiteJob implements ShouldQueue
{
    use Queueable;

    // No reintentar: una llamada a la IA cuesta dinero; un reintento automático
    // duplicaría el gasto. Si falla, se marca el error y el usuario decide.
    public int $tries = 1;
    public int $timeout = 700;

    public function __construct(
        public int $projectId,
        public int $userId,
        public string $mode,        // 'generate' | 'update'
        public string $input,       // prompt o instrucción
        public array $imagePaths = [], // rutas (disco local) de imágenes adjuntas
    ) {}

    public function handle(AnthropicService $ai): void
    {
        $project = Project::find($this->projectId);
        $user    = User::find($this->userId);

        if (!$project || !$user) {
            $this->cleanupImages();
            return;
        }

        $images = $this->loadImages();

        try {
            if ($this->mode === 'update') {
                $recentHistory = collect($project->ai_history ?? [])
                    ->filter(fn ($e) => ($e['type'] ?? '') === 'update')
                    ->take(-6)
                    ->map(fn ($e) => ['role' => $e['role'], 'content' => $e['content']])
                    ->values()
                    ->all();

                $result = $ai->forUser($user)->updateWebsite(
                    $this->input,
                    $project->html ?? '',
                    $project->css ?? '',
                    $project->js ?? '',
                    $recentHistory,
                    $images,
                );

                $this->persist($project, $result, 'update', [
                    'changed' => $result['changed'] ?? [],
                ]);
            } else {
                $result = $ai->forUser($user)->generateWebsite(
                    $this->input,
                    [],
                    fn (string $msg) => $project->update(['ai_progress' => $msg]),
                    $images,
                );

                $this->persist($project, $result, 'generate');
            }
        } catch (\Throwable $e) {
            Log::warning('Generación de web fallida', [
                'project_id' => $this->projectId,
                'mode'       => $this->mode,
                'error'      => $e->getMessage(),
            ]);
            $project->update([
                'ai_status'   => 'error',
                'ai_progress' => $e->getMessage(),
            ]);
        } finally {
            $this->cleanupImages();
        }
    }

    /**
     * Lee las imágenes adjuntas del disco y las prepara como base64 para la IA.
     *
     * @return array<int,array{media_type:string,data:string}>
     */
    private function loadImages(): array
    {
        $images = [];
        foreach ($this->imagePaths as $path) {
            if (!Storage::disk('local')->exists($path)) {
                continue;
            }
            $abs = Storage::disk('local')->path($path);
            $mime = @mime_content_type($abs) ?: 'image/jpeg';
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                continue;
            }
            $images[] = [
                'media_type' => $mime,
                'data'       => base64_encode(Storage::disk('local')->get($path)),
            ];
        }
        return $images;
    }

    private function cleanupImages(): void
    {
        foreach ($this->imagePaths as $path) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Guarda el resultado en el proyecto y deja el chat de IA actualizado.
     */
    private function persist(Project $project, array $result, string $type, array $extraAssistant = []): void
    {
        $assistant = array_merge([
            'role'       => 'assistant',
            'content'    => $result['description'] ?? 'Listo.',
            'created_at' => now()->toISOString(),
            'type'       => $type,
        ], $extraAssistant);

        $project->update(array_merge([
            'html'        => $result['html'] ?? $project->html,
            'css'         => $result['css'] ?? $project->css,
            'js'          => $result['js'] ?? $project->js,
            // Snapshot para poder deshacer el último cambio. Una edición guarda el
            // estado previo; una regeneración completa invalida cualquier snapshot
            // anterior (no hay un "antes" único al que volver).
        ], $type === 'update' ? [
            'previous_html' => $project->html,
            'previous_css'  => $project->css,
            'previous_js'   => $project->js,
        ] : [
            'previous_html' => null,
            'previous_css'  => null,
            'previous_js'   => null,
        ], [
            'ai_history'  => array_merge($project->ai_history ?? [], [
                [
                    'role'       => 'user',
                    'content'    => $this->input,
                    'created_at' => now()->toISOString(),
                    'type'       => $type,
                ],
                $assistant,
            ]),
            'ai_status'   => 'ready',
            'ai_progress' => null,
        ]));

        // Refleja los cambios en el dominio propio si está publicado.
        $project->deployToLiveDomain();
    }

    /**
     * Si el job falla de forma irrecuperable (timeout, etc.), deja el proyecto en
     * un estado consultable por el editor.
     */
    public function failed(\Throwable $e): void
    {
        Project::where('id', $this->projectId)->update([
            'ai_status'   => 'error',
            'ai_progress' => 'La generación tardó demasiado o falló. Inténtalo de nuevo.',
        ]);
    }
}
