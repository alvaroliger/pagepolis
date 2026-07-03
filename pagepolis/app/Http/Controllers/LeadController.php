<?php

namespace App\Http\Controllers;

use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    /**
     * Recibe el envío de un formulario de una web publicada en /s/{slug} y lo
     * guarda como lead + avisa al dueño por email. Endpoint PÚBLICO (lo llama la
     * web del cliente), sin CSRF y con throttle anti-spam. Nunca debe romper:
     * para el visitante, enviar el formulario tiene que "funcionar" siempre.
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $project = Project::where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $project) {
            return response()->json(['ok' => false], 404);
        }

        $fields = $request->input('fields', []);
        if (! is_array($fields) || $fields === []) {
            // Tolerante: acepta también campos planos (form-encoded sin envoltorio).
            $fields = collect($request->except(['_token', 'fields']))
                ->filter(fn ($v) => is_scalar($v))
                ->all();
        }

        $data = $this->extract($fields);

        // Sin nada aprovechable (ni contacto ni mensaje) → ignora en silencio.
        if (! $data['email'] && ! $data['phone'] && ! $data['message']) {
            return response()->json(['ok' => false, 'error' => 'empty'], 422);
        }

        $lead = $project->leads()->create([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'],
            'message' => $data['message'],
            'payload' => $this->sanitizePayload($fields),
            'ip'      => $request->ip(),
        ]);

        // El email no debe tumbar la captura: si falla, el lead ya está guardado.
        try {
            if ($project->user?->email) {
                Mail::to($project->user->email)->queue(new NewLeadMail($project, $lead));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Bandeja de mensajes del dueño: todos los leads de sus webs.
     */
    public function index(): Response
    {
        $user       = auth()->user();
        $projectIds = $user->projects()->pluck('id');

        $unread = Lead::whereIn('project_id', $projectIds)->whereNull('read_at')->count();

        $fetched = Lead::whereIn('project_id', $projectIds)
            ->with('project:id,name,slug')
            ->latest()
            ->limit(200)
            ->get();

        // Only mark as read the leads that were actually fetched and shown.
        $displayedUnreadIds = $fetched->whereNull('read_at')->pluck('id');
        if ($displayedUnreadIds->isNotEmpty()) {
            Lead::whereIn('id', $displayedUnreadIds)->update(['read_at' => now()]);
        }

        $leads = $fetched->map(fn (Lead $l) => [
            'id'         => $l->id,
            'project'    => $l->project?->name,
            'name'       => $l->name,
            'email'      => $l->email,
            'phone'      => $l->phone,
            'message'    => $l->message,
            'is_new'     => $l->read_at === null,
            'created_at' => $l->created_at->diffForHumans(),
        ]);

        return Inertia::render('Leads/Index', [
            'leads'       => $leads,
            'unreadCount' => $unread,
        ]);
    }

    /**
     * Descarga todos los leads del usuario como CSV (sin límite de 200 filas).
     * Incluye BOM UTF-8 para que Excel lo abra correctamente sin configuración.
     */
    public function export(): StreamedResponse
    {
        $user       = auth()->user();
        $projectIds = $user->projects()->pluck('id');

        $leads = Lead::whereIn('project_id', $projectIds)
            ->with('project:id,name')
            ->orderByDesc('created_at')
            ->get();

        $filename = 'mensajes-' . now()->toDateString() . '.csv';

        return response()->streamDownload(function () use ($leads) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para compatibilidad con Excel
            fputcsv($out, ['Fecha', 'Web', 'Nombre', 'Email', 'Teléfono', 'Mensaje']);
            foreach ($leads as $lead) {
                fputcsv($out, [
                    $lead->created_at->format('Y-m-d H:i'),
                    $lead->project?->name ?? '',
                    $lead->name    ?? '',
                    $lead->email   ?? '',
                    $lead->phone   ?? '',
                    $lead->message ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Extrae nombre/email/teléfono/mensaje de un formulario con campos arbitrarios
     * (la IA genera los names libremente), por nombre de campo y, si no, por forma
     * del valor. Con topes de longitud para proteger la BD.
     */
    private function extract(array $fields): array
    {
        $byName = function (array $needles) use ($fields) {
            foreach ($fields as $key => $value) {
                if (! is_scalar($value)) {
                    continue;
                }
                $k = Str::lower((string) $key);
                foreach ($needles as $n) {
                    if (str_contains($k, $n)) {
                        return trim((string) $value);
                    }
                }
            }
            return null;
        };

        $email = $byName(['email', 'correo', 'e-mail', 'mail']);
        if (! $email) {
            foreach ($fields as $value) {
                if (is_scalar($value) && filter_var(trim((string) $value), FILTER_VALIDATE_EMAIL)) {
                    $email = trim((string) $value);
                    break;
                }
            }
        }

        $name    = $byName(['name', 'nombre', 'fullname']);
        $phone   = $byName(['phone', 'tel', 'whatsapp', 'movil', 'móvil', 'celular']);
        $message = $byName(['message', 'mensaje', 'comentario', 'comment', 'consulta', 'texto', 'detalle']);

        if (! $message) {
            // El valor más largo suele ser el del textarea de mensaje.
            $longest = '';
            foreach ($fields as $key => $value) {
                if (! is_scalar($value)) {
                    continue;
                }
                $v = trim((string) $value);
                // No confundas el email/teléfono con el mensaje.
                if ($v !== $email && $v !== $phone && strlen($v) > strlen($longest)) {
                    $longest = $v;
                }
            }
            $message = $longest !== '' ? $longest : null;
        }

        return [
            'name'    => $name ? Str::limit($name, 120, '') : null,
            'email'   => $email ? Str::limit($email, 190, '') : null,
            'phone'   => $phone ? Str::limit($phone, 40, '') : null,
            'message' => $message ? Str::limit($message, 4000, '') : null,
        ];
    }

    /** Guarda solo pares escalares y acota tamaño para no almacenar basura enorme. */
    private function sanitizePayload(array $fields): array
    {
        $clean = [];
        foreach ($fields as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            $clean[Str::limit((string) $key, 60, '')] = Str::limit((string) $value, 2000, '');
            if (count($clean) >= 30) {
                break;
            }
        }
        return $clean;
    }
}
