<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'baseDomain'     => config('app.sites_base_domain', 'pagepolis.com'),
        ]);
    }

    /**
     * Publicación gratuita en pagepolis.com/s/slug.
     *
     * Disponible para cualquier usuario (tier gratis). Los sitios de usuarios
     * sin suscripción muestran un badge "Hecho con Pagepolis" (ver SiteController).
     * El dominio propio/subdominio sigue requiriendo suscripción (DomainController).
     */
    public function publishFree(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $user = auth()->user();

        $project = Project::where('id', $request->project_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Límite anti-abuso: cuántas webs puede tener publicadas un usuario gratis.
        // El check de cuenta y el publish van en el mismo UPDATE para que sea atómico:
        // dos requests concurrentes ya no pueden colar ambas pasando el límite (TOCTOU).
        if (!$user->isSubscribed() && $project->status !== 'published') {
            $max = config('pagepolis.limits.free_max_published', 3);

            $reserved = DB::table('projects')
                ->where('id', $project->id)
                ->where('user_id', $user->id)
                ->where('status', '!=', 'published')
                ->whereRaw(
                    '(select count(*) from projects where user_id = ? and status = ? and deleted_at is null) < ?',
                    [$user->id, 'published', $max]
                )
                ->update(['status' => 'published', 'published_at' => now()]);

            if (!$reserved) {
                return response()->json([
                    'error'   => "El plan gratuito permite publicar hasta {$max} webs. Mejora a un plan de pago para publicar más.",
                    'upgrade' => true,
                ], 402);
            }

            $project->refresh();
        } else {
            $project->update([
                'status'       => 'published',
                'published_at' => now(),
            ]);
        }

        Domain::updateOrCreate(
            ['project_id' => $project->id],
            [
                'user_id' => $user->id,
                'domain'  => $project->slug,
                'type'    => 'path',
                'status'  => 'active',
            ]
        );

        return response()->json([
            'success' => true,
            'url'     => config('app.url') . '/s/' . $project->slug,
        ]);
    }

    /**
     * Despublica un proyecto: deja de ser accesible en /s/{slug} (o en su
     * dominio propio) sin borrar nada. Libera al instante el hueco del límite
     * de publicaciones del tier gratuito, que solo cuenta status='published'.
     */
    public function unpublish(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::where('id', $request->project_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $project->update([
            'status'       => 'draft',
            'published_at' => null,
        ]);

        return response()->json(['success' => true]);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Publish/Success', [
            'sessionId' => $request->session_id,
        ]);
    }
}
