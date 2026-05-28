<?php

namespace App\Http\Controllers;

use App\Models\Project;
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
            'project'        => $project ? ['id' => $project->id, 'name' => $project->name] : null,
            'isSubscribed'   => $user->isSubscribed(),
            'inGracePeriod'  => $user->inGracePeriod(),
            'stripeKey'      => config('services.stripe.key'),
        ]);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Publish/Success', [
            'sessionId' => $request->session_id,
        ]);
    }
}
