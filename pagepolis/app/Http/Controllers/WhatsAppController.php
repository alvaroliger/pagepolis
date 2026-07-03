<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Services\AnthropicService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Agente IA de Pagepolis vía WhatsApp Business.
 * El cliente puede editar su web enviando mensajes de texto.
 */
class WhatsAppController extends Controller
{
    // Número máximo de cambios por día vía WhatsApp (mismo límite que en la app)
    private const DAILY_LIMIT = 20;

    /**
     * Verificación del webhook por Meta/Twilio.
     */
    public function verify(Request $request): Response
    {
        $token = config('services.whatsapp.verify_token');

        if (
            $request->get('hub_mode')       === 'subscribe' &&
            $request->get('hub_verify_token') === $token
        ) {
            return response($request->get('hub_challenge'), 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Recibe mensajes entrantes de WhatsApp y responde con cambios en la web.
     */
    public function webhook(Request $request, AnthropicService $ai): Response
    {
        // Verificar firma del webhook (Meta)
        if (!$this->validateSignature($request)) {
            Log::warning('WhatsApp webhook: firma inválida');
            return response('Unauthorized', 401);
        }

        try {
            $entry   = $request->input('entry.0');
            $changes = $entry['changes'][0] ?? null;
            if (!$changes || $changes['field'] !== 'messages') {
                return response('ok', 200);
            }

            $value   = $changes['value'];
            $message = $value['messages'][0] ?? null;
            if (!$message || $message['type'] !== 'text') {
                // Ignorar mensajes que no sean texto (imágenes, audio, etc.)
                $this->sendWhatsApp($value['contacts'][0]['wa_id'] ?? '', $this->helpMessage());
                return response('ok', 200);
            }

            $from        = $message['from'];    // Número del usuario (con código de país)
            $text        = trim($message['text']['body'] ?? '');
            $messageId   = $message['id'];

            // Marcar como leído
            $this->markRead($messageId);

            // Buscar usuario por teléfono
            $user = User::where('whatsapp_phone', $from)->first();

            if (!$user) {
                $this->sendWhatsApp($from,
                    "Hola, soy el asistente de Pagepolis.\n\n" .
                    "No encontré ninguna cuenta vinculada a este número.\n\n" .
                    "Para vincular tu cuenta, entra en *pagepolis.com*, ve a tu *Perfil* y añade tu número de WhatsApp."
                );
                return response('ok', 200);
            }

            // Procesar comando
            $response = $this->processMessage($user, $text, $ai);
            $this->sendWhatsApp($from, $response);

        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook error: ' . $e->getMessage());
        }

        return response('ok', 200);
    }

    private function processMessage(User $user, string $text, AnthropicService $ai): string
    {
        $lower = mb_strtolower($text);

        // Comandos de ayuda
        if (in_array($lower, ['ayuda', 'help', 'hola', 'menu', 'menú', '?'])) {
            return $this->helpMessage();
        }

        // Listar proyectos
        if (in_array($lower, ['mis webs', 'mis proyectos', 'webs', 'proyectos', 'lista'])) {
            return $this->listProjects($user);
        }

        // Seleccionar proyecto: "web 1", "web 2", etc.
        if (preg_match('/^web\s+(\d+)$/i', $text, $m)) {
            return $this->selectProject($user, (int) $m[1]);
        }

        // Cambios a la web activa
        return $this->applyChange($user, $text, $ai);
    }

    private function listProjects(User $user): string
    {
        $projects = $user->projects()->where('status', '!=', 'deleted')->get(['id', 'name', 'status']);

        if ($projects->isEmpty()) {
            return "No tienes ninguna web creada aún.\n\nEntra en *pagepolis.com* para crear tu primera web.";
        }

        $lines = ["Tus webs en Pagepolis:\n"];
        foreach ($projects as $i => $p) {
            $status = $p->status === 'published' ? '(publicada)' : '(borrador)';
            $lines[] = ($i + 1) . ". *{$p->name}* {$status}";
        }
        $lines[] = "\nEscribe *web 1*, *web 2*, etc. para seleccionar la que quieres editar.";

        return implode("\n", $lines);
    }

    private function selectProject(User $user, int $number): string
    {
        $projects = $user->projects()->where('status', '!=', 'deleted')->get();
        $project  = $projects->get($number - 1);

        if (!$project) {
            return "No encontré la web número {$number}. Escribe *mis webs* para ver la lista.";
        }

        // Guardar selección en sesión (usamos una columna temporal en el user)
        $user->update(['whatsapp_active_project' => $project->id]);

        return "Web seleccionada: *{$project->name}*\n\nAhora dime qué quieres cambiar. Por ejemplo:\n- \"Cambia el título a Mi Empresa\"\n- \"Pon el fondo de color azul\"\n- \"Añade mi número de teléfono 612345678\"\n- \"Cambia el horario a lunes-viernes 9-18h\"";
    }

    private function applyChange(User $user, string $instruction, AnthropicService $ai): string
    {
        // Verificar que hay un proyecto seleccionado
        $projectId = $user->whatsapp_active_project;
        if (!$projectId) {
            return "Primero selecciona qué web quieres editar. Escribe *mis webs* para ver tus webs.";
        }

        $project = Project::find($projectId);
        if (!$project || $project->user_id !== $user->id) {
            $user->update(['whatsapp_active_project' => null]);
            return "No encontré la web seleccionada. Escribe *mis webs* para elegir otra.";
        }

        // Verificar límite diario
        $today = now()->toDateString();
        if ($user->ai_calls_reset_date?->toDateString() !== $today) {
            $user->update(['ai_calls_today' => 0, 'ai_calls_reset_date' => $today]);
            $user->refresh();
        }

        $dailyLimit = $user->isSubscribed() ? self::DAILY_LIMIT : 5;
        if ($user->ai_calls_today >= $dailyLimit) {
            return "Has llegado al límite de cambios diarios ({$dailyLimit}).\n\n" .
                   ($user->isSubscribed()
                       ? "Se reinicia mañana a medianoche."
                       : "Activa tu plan en *pagepolis.com* para tener más cambios.");
        }

        // Aplicar cambio con IA
        try {
            $result = $ai->forUser($user)->updateWebsite(
                $instruction,
                $project->html ?? '',
                $project->css  ?? '',
                $project->js   ?? ''
            );

            $project->update([
                'html' => $result['html'] ?? $project->html,
                'css'  => $result['css']  ?? $project->css,
                'js'   => $result['js']   ?? $project->js,
            ]);

            $project->deployToLiveDomain();

            $user->increment('ai_calls_today');

            $description = $result['description'] ?? 'Cambio aplicado correctamente.';
            $remaining   = $dailyLimit - $user->ai_calls_today;

            $msg = "Listo. {$description}\n\n";
            $msg .= "Los cambios ya están guardados en *{$project->name}*.\n";

            if ($project->status === 'published') {
                $msg .= "Tu web publicada se actualizará en unos segundos.";
            } else {
                $msg .= "Para publicarla, entra en *pagepolis.com* y pulsa Publicar.";
            }

            if ($remaining <= 3) {
                $msg .= "\n\n_Te quedan {$remaining} cambios hoy._";
            }

            return $msg;

        } catch (\Exception $e) {
            return "Lo siento, no pude aplicar ese cambio: {$e->getMessage()}\n\nInténtalo de nuevo con una descripción diferente.";
        }
    }

    private function helpMessage(): string
    {
        return "Hola, soy el asistente de Pagepolis. Te ayudo a editar tu web sin tocar el ordenador.\n\n" .
               "*Comandos disponibles:*\n" .
               "- *mis webs* — ver todas tus webs\n" .
               "- *web 1* — seleccionar la web nº 1\n\n" .
               "*Ejemplos de cambios:*\n" .
               "- \"Cambia el título a Mi Empresa\"\n" .
               "- \"Pon el fondo azul oscuro\"\n" .
               "- \"Añade mi email contacto@miempresa.com\"\n" .
               "- \"Cambia el horario a lunes-viernes 9-18h\"\n" .
               "- \"Añade una sección de precios\"\n\n" .
               "Para cambios avanzados, usa el editor en *pagepolis.com*";
    }

    private function sendWhatsApp(string $to, string $message): void
    {
        $token   = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');

        if (!$token || !$phoneId || !$to) return;

        Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);
    }

    private function markRead(string $messageId): void
    {
        $token   = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_id');
        if (!$token || !$phoneId) return;

        Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'status'            => 'read',
                'message_id'        => $messageId,
            ]);
    }

    private function validateSignature(Request $request): bool
    {
        $secret    = config('services.whatsapp.app_secret');
        $signature = $request->header('X-Hub-Signature-256', '');

        if (!$secret) return true; // Sin secret configurado, pasar (desarrollo)

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($expected, $signature);
    }
}
