<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Project;
use App\Services\NamecheapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DomainController extends Controller
{
    private array $forbidden = [
        'instaweb.com', 'google.com', 'facebook.com', 'apple.com',
        'amazon.com', 'microsoft.com', 'twitter.com', 'instagram.com',
    ];

    public function check(Request $request, NamecheapService $namecheap): JsonResponse
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

        $project = Project::where('id', $request->project_id)
            ->where('user_id', auth()->id())
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
