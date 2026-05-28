<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AnthropicService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
        $this->model  = config('services.anthropic.model', 'claude-sonnet-4-20250514');
    }

    public function generateWebsite(string $userPrompt, array $templateContext = []): \Generator
    {
        $systemPrompt = <<<SYSTEM
Eres un experto desarrollador web y diseñador UI/UX. Tu tarea es generar páginas web completas,
modernas, responsivas y visualmente impresionantes basándote en las instrucciones del usuario.

REGLAS ESTRICTAS:
1. Devuelve ÚNICAMENTE un JSON válido con esta estructura exacta:
{"html": "...código HTML completo del body...", "css": "...CSS completo...", "js": "...JavaScript opcional..."}
2. El HTML debe ser semántico, accesible y responsivo (mobile-first con CSS Grid/Flexbox).
3. El CSS debe ser moderno: variables CSS, animaciones suaves, diseño atractivo.
4. Usa Google Fonts (importa vía @import en el CSS).
5. NO incluyas <html>, <head>, <body> en el HTML — solo el contenido del body.
6. El CSS ya tiene un reset base, no lo incluyas.
7. Haz el diseño visualmente impresionante, profesional y ÚNICO, no genérico.
8. Asegúrate de que todas las secciones tengan contenido real de placeholder coherente con el negocio.
9. Incluye navegación, hero section, secciones de contenido relevantes y footer.
10. Optimiza para conversión: CTAs claros, jerarquía visual, colores coherentes.
SYSTEM;

        $messages = [];

        if (!empty($templateContext)) {
            $messages[] = [
                'role'    => 'user',
                'content' => 'Plantilla base seleccionada: ' . json_encode($templateContext),
            ];
            $messages[] = [
                'role'    => 'assistant',
                'content' => 'Entendido. Utilizaré esa plantilla como base y la adaptaré según tus instrucciones.',
            ];
        }

        $messages[] = [
            'role'    => 'user',
            'content' => $userPrompt,
        ];

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 8192,
            'system'     => $systemPrompt,
            'messages'   => $messages,
            'stream'     => false,
        ]);

        $data    = $response->json();
        $content = $data['content'][0]['text'] ?? '';

        $content = preg_replace('/```json|```/m', '', $content);
        $parsed  = json_decode(trim($content), true);

        if (!$parsed || !isset($parsed['html'])) {
            throw new \Exception('La IA no devolvió un formato válido.');
        }

        yield $parsed;
    }
}
