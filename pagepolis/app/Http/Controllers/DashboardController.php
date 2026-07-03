<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user    = auth()->user();
        $baseUrl = config('app.url');

        $projects = $user->projects()
            ->with('domain')
            ->orderByDesc('updated_at')
            ->get();

        // Visitas de los últimos 30 días por proyecto (una sola consulta).
        $since      = now()->subDays(29)->toDateString();
        $views30 = DB::table('page_views')
            ->whereIn('project_id', $projects->pluck('id'))
            ->where('viewed_on', '>=', $since)
            ->selectRaw('project_id, SUM(count) as t')
            ->groupBy('project_id')
            ->pluck('t', 'project_id');

        $mapped = $projects->map(fn ($p) => [
            'id'          => $p->id,
            'name'        => $p->name,
            'status'      => $p->status,
            'slug'        => $p->slug,
            'updated_at'  => $p->updated_at->diffForHumans(),
            'views_30d'   => (int) ($views30[$p->id] ?? 0),
            'domain'      => $p->domain?->domain,
            'live_url'    => match ($p->domain?->type) {
                'custom'    => 'https://' . $p->domain->domain,
                'subdomain' => 'https://' . $p->domain->domain,
                'path'      => $baseUrl . '/s/' . $p->slug,
                default     => null,
            },
            'preview_url' => route('projects.preview', $p->id),
        ]);

        // Papelera: proyectos eliminados (soft delete) que se pueden restaurar.
        $trashed = $user->projects()->onlyTrashed()->orderByDesc('deleted_at')->get()
            ->map(fn ($p) => [
                'id'         => $p->id,
                'name'       => $p->name,
                'deleted_at' => $p->deleted_at->diffForHumans(),
            ]);

        // Onboarding checklist: qué pasos clave ha completado ya el usuario.
        // 'domain' solo se activa con dominio propio/subdominio (paid), no con /s/slug.
        $onboarding = [
            'published' => $projects->contains('status', 'published'),
            'domain'    => $user->domains()
                ->whereIn('type', ['custom', 'subdomain'])
                ->where('status', 'active')
                ->exists(),
        ];

        return Inertia::render('Dashboard/Index', [
            'projects'       => $mapped,
            'trashed'        => $trashed,
            'isSubscribed'   => $user->isSubscribed(),
            'inGracePeriod'  => $user->inGracePeriod(),
            'onboarding'     => $onboarding,
        ]);
    }
}
