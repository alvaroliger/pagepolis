<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReportMail;
use App\Models\Domain;
use App\Models\PageView;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReports extends Command
{
    protected $signature   = 'twtyg:weekly-reports';
    protected $description = 'Envía el informe semanal de visitas a todos los usuarios activos';

    public function handle(): void
    {
        $baseUrl    = config('app.url');
        $baseDomain = config('app.sites_base_domain', 'twtyg.com');

        $users = User::whereHas('projects', fn($q) => $q->where('status', 'published'))
            ->get();

        $this->info("Enviando informes a {$users->count()} usuarios...");

        foreach ($users as $user) {
            try {
                $stats = $this->buildStats($user, $baseUrl, $baseDomain);
                Mail::to($user->email)->queue(new WeeklyReportMail($user, $stats));
                $this->line("  ✓ {$user->email}");
            } catch (\Exception $e) {
                $this->error("  ✗ {$user->email}: " . $e->getMessage());
            }
        }

        $this->info('Informes enviados.');
    }

    private function buildStats(User $user, string $baseUrl, string $baseDomain): array
    {
        $now      = now();
        $weekAgo  = $now->copy()->subDays(7);
        $twoWeeks = $now->copy()->subDays(14);

        $projects = Project::where('user_id', $user->id)
            ->where('status', 'published')
            ->with(['domain', 'pageViews' => fn($q) => $q->whereBetween('viewed_on', [$twoWeeks->toDateString(), $now->toDateString()])])
            ->get();

        $projectStats = $projects->map(function (Project $project) use ($weekAgo, $baseUrl, $baseDomain) {
            $weekViews = $project->pageViews
                ->where('viewed_on', '>=', $weekAgo->toDateString())
                ->sum('count');

            $domain = $project->domain;
            $url    = match ($domain?->type) {
                'custom'    => 'https://' . $domain->domain,
                'subdomain' => 'https://' . $domain->domain,
                'path'      => $baseUrl . '/s/' . $project->slug,
                default     => $baseUrl . '/s/' . $project->slug,
            };

            return [
                'name'        => $project->name,
                'url'         => $url,
                'week_visits' => (int) $weekViews,
            ];
        })->values()->all();

        $totalWeek     = (int) PageView::whereIn('project_id', $projects->pluck('id'))
            ->where('viewed_on', '>=', $weekAgo->toDateString())
            ->sum('count');

        $totalPrevWeek = (int) PageView::whereIn('project_id', $projects->pluck('id'))
            ->whereBetween('viewed_on', [$twoWeeks->toDateString(), $weekAgo->subDay()->toDateString()])
            ->sum('count');

        return [
            'projects'        => $projectStats,
            'total_week'      => $totalWeek,
            'total_prev_week' => $totalPrevWeek,
            'tip'             => $this->getTip($totalWeek, $totalPrevWeek),
        ];
    }

    private function getTip(int $week, int $prev): string
    {
        if ($week === 0) {
            return 'Tu web está publicada pero aún sin visitas. Compártela en redes sociales y en tu perfil de Google My Business para empezar a recibir tráfico.';
        }
        if ($week < $prev * 0.7) {
            return 'Las visitas bajaron esta semana. Prueba a actualizar el contenido con la IA — "añade una oferta especial de esta semana" suele atraer más visitas recurrentes.';
        }
        if ($week > $prev * 1.3) {
            return '¡Buena semana! Las visitas subieron. Aprovecha el momento para pedir reseñas a tus clientes recientes.';
        }
        return 'Mantén la web actualizada con tus novedades. Puedes pedirle a la IA que cambie el texto del hero o añada nuevas secciones en segundos desde el editor.';
    }
}
