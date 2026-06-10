<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class AnthropicService
{
    private string $apiKey;
    private string $paidModel;
    private string $freeModel;
    private ?string $activeModel = null;

    public function __construct(private AiBudgetGuard $budget)
    {
        $this->apiKey    = config('services.anthropic.key');
        $this->paidModel = config('services.anthropic.model', 'claude-opus-4-8');
        $this->freeModel = config('services.anthropic.model_free', 'claude-sonnet-4-6');
    }

    /**
     * Selecciona el modelo según el tier del usuario: los de pago usan el mejor
     * modelo (máxima calidad); los gratis uno más económico (protege el margen).
     */
    public function forUser(?User $user): static
    {
        $this->activeModel = ($user && $user->isSubscribed()) ? $this->paidModel : $this->freeModel;

        return $this;
    }

    private function model(): string
    {
        return $this->activeModel ?? $this->freeModel;
    }

    /**
     * Genera una web completa desde cero.
     */
    public function generateWebsite(string $userPrompt, array $templateContext = []): array
    {
        $system = <<<SYSTEM
Eres un experto desarrollador web y diseñador UI/UX. Tu tarea es generar páginas web completas,
modernas, responsivas y visualmente impresionantes basándote en las instrucciones del usuario.

REGLAS ESTRICTAS:
1. Devuelve ÚNICAMENTE un JSON válido con esta estructura exacta:
{"html":"...código HTML completo del body...","css":"...CSS completo...","js":"...JavaScript opcional...","description":"...descripción en español de qué has creado en 1-2 frases..."}
2. El HTML debe ser semántico, accesible y responsivo (mobile-first con CSS Grid/Flexbox).
3. El CSS debe ser moderno: variables CSS, animaciones suaves, diseño atractivo.
4. Usa Google Fonts (importa vía @import en el CSS).
5. NO incluyas <html>, <head>, <body> en el HTML — solo el contenido del body.
6. Haz el diseño visualmente impresionante, profesional y ÚNICO, no genérico.
7. Incluye navegación, hero section, secciones de contenido relevantes y footer.
8. Optimiza para conversión: CTAs claros, jerarquía visual, colores coherentes.
SYSTEM;

        $messages = [];

        if (!empty($templateContext)) {
            $messages[] = ['role' => 'user',      'content' => 'Plantilla base: ' . json_encode($templateContext)];
            $messages[] = ['role' => 'assistant', 'content' => 'Entendido, usaré esa plantilla como base.'];
        }

        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        return $this->callApi($system, $messages, 8192);
    }

    /**
     * Aplica un cambio quirúrgico a una web existente.
     * Solo modifica lo que el usuario pide, conserva todo lo demás.
     */
    public function updateWebsite(
        string $instruction,
        string $currentHtml,
        string $currentCss,
        string $currentJs,
        array  $chatHistory = []
    ): array {
        $system = <<<SYSTEM
Eres un asistente experto en mantenimiento de páginas web. Tu trabajo es aplicar cambios PRECISOS y QUIRÚRGICOS a una web existente.

REGLAS CRÍTICAS:
1. Devuelve ÚNICAMENTE un JSON válido con esta estructura:
{"html":"...HTML actualizado...","css":"...CSS actualizado...","js":"...JS actualizado...","description":"...explica en español coloquial qué has cambiado, en 1 frase corta...","changed":["html"|"css"|"js"]}
2. Modifica SOLO lo que el usuario ha pedido. Si pide cambiar el color, solo cambia el color. Si pide añadir una sección, añádela sin tocar las demás.
3. Conserva TODA la estructura, estilos y contenido que no sean parte del cambio solicitado.
4. NO regeneres la web desde cero bajo ningún concepto.
5. La "description" debe sonar natural: "He cambiado el color principal a azul y actualizado los botones" NO "Se han modificado las propiedades CSS..."
6. "changed" es un array con qué archivos tocaste: ["html"], ["css"], ["html","css"], etc.
SYSTEM;

        // Construir historial de conversación para contexto
        $messages = [];
        foreach ($chatHistory as $entry) {
            $messages[] = ['role' => $entry['role'], 'content' => $entry['content']];
        }

        $messages[] = [
            'role'    => 'user',
            'content' => "Esta es mi web actual:\n\n--- HTML ---\n{$currentHtml}\n\n--- CSS ---\n{$currentCss}\n\n--- JS ---\n{$currentJs}\n\n--- CAMBIO SOLICITADO ---\n{$instruction}",
        ];

        return $this->callApi($system, $messages, 8192);
    }

    /**
     * Genera metadatos SEO para una web existente.
     */
    public function generateSeoMeta(string $html, string $businessHint = ''): array
    {
        $system = <<<SYSTEM
Eres un experto en SEO. Analiza el contenido HTML de una web y genera metadatos SEO optimizados.
Devuelve ÚNICAMENTE JSON válido con esta estructura:
{
  "title": "...título SEO de máximo 60 caracteres...",
  "description": "...meta description de máximo 155 caracteres, con llamada a la acción...",
  "keywords": "...5-8 keywords separadas por comas...",
  "og_title": "...título para redes sociales...",
  "og_description": "...descripción para redes sociales...",
  "schema": {...objeto JSON-LD LocalBusiness o WebSite según el contenido...}
}
SYSTEM;

        $content  = $businessHint ? "Pista del negocio: {$businessHint}\n\n" : '';
        $content .= "HTML: " . substr($html, 0, 3000);

        // El SEO es una tarea ligera: usa siempre el modelo económico.
        $this->activeModel = $this->freeModel;

        $result = $this->callApi($system, [['role' => 'user', 'content' => $content]], 1024);

        return $result;
    }

    private function callApi(string $system, array $messages, int $maxTokens, int $attempt = 1): array
    {
        // Guardián de presupuesto: si se ha alcanzado el tope mensual, no se
        // hacen más llamadas (nunca se gasta de más sin permiso explícito).
        if (!$this->budget->isWithinBudget()) {
            \Illuminate\Support\Facades\Log::warning('IA pausada: presupuesto alcanzado', [
                'motivo'       => $this->budget->blockedReason(),
                'gasto_hoy'    => $this->budget->todayUsd(),
                'gasto_mes'    => $this->budget->monthToDateUsd(),
                'tope_diario'  => $this->budget->dailyBudgetUsd(),
                'tope_mensual' => $this->budget->monthlyBudgetUsd(),
            ]);
            throw new \Exception('El servicio de IA está en pausa temporal. Vuelve a intentarlo más tarde o contacta con soporte.');
        }

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model(),
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => $messages,
        ]);

        // Registrar el consumo real (Anthropic cobra los tokens aunque la
        // respuesta venga malformada), para que el guardián lo contabilice.
        if ($response->successful()) {
            $usage = $response->json('usage', []);
            $this->budget->record(
                $this->model(),
                (int) ($usage['input_tokens'] ?? 0),
                (int) ($usage['output_tokens'] ?? 0),
            );
        }

        if ($response->failed()) {
            $status = $response->status();

            // Reintentar una vez si el servicio está saturado
            if ($status === 529 && $attempt === 1) {
                sleep(3);
                return $this->callApi($system, $messages, $maxTokens, 2);
            }

            $friendly = match(true) {
                $status === 401 => 'La configuración de la IA no es válida. Contacta con soporte.',
                $status === 429 => 'La IA está recibiendo demasiadas peticiones. Espera un momento e inténtalo de nuevo.',
                $status === 529 => 'La IA está saturada ahora mismo. Inténtalo en unos minutos.',
                $status >= 500  => 'El servicio de IA no está disponible temporalmente. Inténtalo en unos minutos.',
                default         => 'No se pudo conectar con la IA. Inténtalo de nuevo.',
            };
            throw new \Exception($friendly);
        }

        $text = $response->json('content.0.text', '');
        // Limpiar posibles bloques de código markdown
        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/\s*```$/m', '', $text);
        // Extraer solo el JSON si hay texto antes/después
        if (preg_match('/\{[\s\S]*\}/m', $text, $matches)) {
            $text = $matches[0];
        }

        $parsed = json_decode(trim($text), true);

        if (!$parsed || !is_array($parsed)) {
            // Reintentar una vez si el JSON está malformado
            if ($attempt === 1) {
                sleep(1);
                return $this->callApi($system, $messages, $maxTokens, 2);
            }
            throw new \Exception('La IA no pudo procesar tu solicitud. Inténtalo con una descripción diferente.');
        }

        return $parsed;
    }
}
