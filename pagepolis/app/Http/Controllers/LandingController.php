<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response
    {
        $sitesPublished = Cache::remember('stats.sites_published', 3600, fn () =>
            Project::where('status', 'published')->count()
        );

        return Inertia::render('Landing', [
            'stats' => ['sites_published' => $sitesPublished],
        ]);
    }
}
