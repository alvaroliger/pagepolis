<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user     = auth()->user();
        $baseUrl    = config('app.url');

        $projects = $user->projects()
            ->with('domain')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'status'      => $p->status,
                'slug'        => $p->slug,
                'updated_at'  => $p->updated_at->diffForHumans(),
                'domain'      => $p->domain?->domain,
                'live_url'    => match ($p->domain?->type) {
                    'custom'    => 'https://' . $p->domain->domain,
                    'subdomain' => 'https://' . $p->domain->domain,
                    'path'      => $baseUrl . '/s/' . $p->slug,
                    default     => null,
                },
                'preview_url' => route('projects.preview', $p->id),
            ]);

        return Inertia::render('Dashboard/Index', [
            'projects'       => $projects,
            'isSubscribed'   => $user->isSubscribed(),
            'inGracePeriod'  => $user->inGracePeriod(),
        ]);
    }
}
