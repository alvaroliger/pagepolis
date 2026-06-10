<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * Panel de analítica de visitas del usuario (datos de la tabla page_views).
     */
    public function index(): Response
    {
        $user       = auth()->user();
        $projects   = $user->projects()->orderByDesc('updated_at')->get();
        $projectIds = $projects->pluck('id')->all();

        $since = now()->subDays(29)->toDateString();

        // Visitas diarias agregadas (todas las webs) — últimos 30 días.
        $daily = DB::table('page_views')
            ->whereIn('project_id', $projectIds)
            ->where('viewed_on', '>=', $since)
            ->selectRaw('viewed_on, SUM(count) as total')
            ->groupBy('viewed_on')
            ->pluck('total', 'viewed_on');

        // Serie de 30 días rellenando los huecos con 0.
        $series = [];
        for ($i = 29; $i >= 0; $i--) {
            $date     = now()->subDays($i)->toDateString();
            $series[] = ['date' => $date, 'count' => (int) ($daily[$date] ?? 0)];
        }

        $totalAll = (int) DB::table('page_views')->whereIn('project_id', $projectIds)->sum('count');
        $total30  = array_sum(array_column($series, 'count'));
        $total7   = (int) collect($series)->slice(-7)->sum('count');

        // Totales por proyecto.
        $totalByProject = DB::table('page_views')->whereIn('project_id', $projectIds)
            ->selectRaw('project_id, SUM(count) as t')->groupBy('project_id')->pluck('t', 'project_id');
        $d30ByProject = DB::table('page_views')->whereIn('project_id', $projectIds)
            ->where('viewed_on', '>=', $since)
            ->selectRaw('project_id, SUM(count) as t')->groupBy('project_id')->pluck('t', 'project_id');

        $perProject = $projects->map(fn ($p) => [
            'id'          => $p->id,
            'name'        => $p->name,
            'status'      => $p->status,
            'views_total' => (int) ($totalByProject[$p->id] ?? 0),
            'views_30d'   => (int) ($d30ByProject[$p->id] ?? 0),
        ])->all();

        return Inertia::render('Analytics/Index', [
            'series'   => $series,
            'totals'   => ['all' => $totalAll, 'd30' => $total30, 'd7' => $total7],
            'projects' => $perProject,
        ]);
    }
}
