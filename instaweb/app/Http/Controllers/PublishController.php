<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublishController extends Controller
{
    public function index(Request $request): Response
    {
        $project = null;
        if ($request->project_id) {
            $project = Project::where('id', $request->project_id)
                ->where('user_id', auth()->id())
                ->first();
        }

        $user = auth()->user();

        return Inertia::render('Publish/Index', [
            'project'        => $project ? ['id' => $project->id, 'name' => $project->name, 'slug' => $project->slug] : null,
            'isSubscribed'   => $user->isSubscribed(),
            'inGracePeriod'  => $user->inGracePeriod(),
            'stripeKey'      => config('services.stripe.key'),
            'baseDomain'     => config('app.sites_base_domain', 'twtyg.com'),
        ]);
    }

    /**
     * Publicación gratuita en twtyg.com/s/slug — no requiere pago.
     */
    public function publishFree(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::where('id', $request->project_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Crear o actualizar el registro de dominio tipo "path"
        Domain::updateOrCreate(
            ['project_id' => $project->id],
            [
                'user_id' => auth()->id(),
                'domain'  => $project->slug,
                'type'    => 'path',
                'status'  => 'active',
            ]
        );

        $project->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        $url = config('app.url') . '/s/' . $project->slug;

        return response()->json([
            'success' => true,
            'url'     => $url,
        ]);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Publish/Success', [
            'sessionId' => $request->session_id,
        ]);
    }
}
