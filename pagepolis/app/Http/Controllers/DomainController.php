<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Project;
use App\Services\DinahostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DomainController extends Controller
{
    private array $forbidden = [
        'pagepolis.com', 'google.com', 'facebook.com', 'apple.com',
        'amazon.com', 'microsoft.com', 'twitter.com', 'instagram.com',
    ];

    public function check(Request $request, DinahostingService $namecheap): JsonResponse
    {
        $request->validate([
            'domain' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]\.[a-z]{2,}$/i'],
        ]);

        $domain = strtolower($request->domain);

        if (in_array($domain, $this->forbidden)) {
            throw ValidationException::withMessages(['domain' => 'Este dominio no está disponible.']);
        }

        try {
            $result = $namecheap->checkAvailability($domain);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['available' => false, 'error' => $e->getMessage()]);
        }
    }

    public function reserve(Request $request): JsonResponse
    {
        $request->validate([
            'domain'     => ['required', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)+$/i'],
            'type'       => 'required|in:custom,subdomain',
            'project_id' => 'required|exists:projects,id',
        ]);

        $user = auth()->user();

        $project = Project::where('id', $request->project_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Reservar SIEMPRE se permite (guarda la intención). NO se exige suscripción
        // aquí: si no la tiene, el usuario pasa al pago y, tras confirmarlo, el webhook
        // de Stripe (provisionPendingDomain) provisiona el dominio. Antes esto devolvía
        // 402 y bloqueaba el embudo de pago de los usuarios nuevos.
        $domain = Domain::updateOrCreate(
            ['project_id' => $project->id],
            [
                'user_id' => $user->id,
                'domain'  => $request->domain,
                'type'    => $request->type,
                'status'  => 'pending',
            ]
        );

        // Si ya está suscrito (o en periodo de gracia), se provisiona ya. El job solo
        // actúa sobre dominios 'pending', así que no hay doble provisión con el webhook.
        if ($user->isSubscribed() || $user->inGracePeriod()) {
            \App\Jobs\PurchaseDomain::dispatch($domain);

            return response()->json([
                'success'      => true,
                'provisioning' => true,
                'domain_id'    => $domain->id,
                'message'      => 'Estamos publicando tu web en ' . $domain->domain . '. Tardará unos minutos.',
            ]);
        }

        // No suscrito: hay que pasar por el pago. Se provisiona tras confirmarlo.
        return response()->json([
            'success'       => true,
            'needs_payment' => true,
            'domain_id'     => $domain->id,
        ]);
    }
}
