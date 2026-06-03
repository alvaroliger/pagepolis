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
        'twtyg.com', 'google.com', 'facebook.com', 'apple.com',
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
            'domain'     => 'required|string',
            'type'       => 'required|in:custom,subdomain',
            'project_id' => 'required|exists:projects,id',
        ]);

        $user = auth()->user();
        if (!$user->isSubscribed() && !$user->inGracePeriod()) {
            return response()->json([
                'message' => 'Necesitas una suscripción activa para reservar un dominio.',
                'upgrade' => true,
            ], 402);
        }

        $project = Project::where('id', $request->project_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $domain = Domain::updateOrCreate(
            ['project_id' => $project->id],
            [
                'user_id' => auth()->id(),
                'domain'  => $request->domain,
                'type'    => $request->type,
                'status'  => 'pending',
            ]
        );

        return response()->json(['success' => true, 'domain_id' => $domain->id]);
    }
}
