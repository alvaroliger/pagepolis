<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AiBudgetGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(AiBudgetGuard $budget): Response
    {
        $users = User::withCount(['projects', 'domains'])
            ->with('subscriptions')
            ->orderByDesc('created_at')
            ->paginate(50);

        $stats = [
            'total_users'   => User::count(),
            'active_subs'   => User::whereHas('subscriptions', fn ($q) => $q->where('stripe_status', 'active'))->count(),
            'total_projects'=> \App\Models\Project::count(),
            'active_domains'=> \App\Models\Domain::where('status', 'active')->count(),
        ];

        // Gasto de IA del mes vs presupuesto (control de coste / abuso).
        $ai = [
            'month_to_date' => round($budget->monthToDateUsd(), 2),
            'today'         => round($budget->todayUsd(), 2),
            'monthly_budget'=> round($budget->monthlyBudgetUsd(), 2),
            'daily_budget'  => round($budget->dailyBudgetUsd(), 2),
            'used_percent'  => $budget->usedPercent(),
            'paused'        => !$budget->isWithinBudget(),
        ];

        return Inertia::render('Admin/Index', [
            'users' => $users,
            'stats' => $stats,
            'ai'    => $ai,
        ]);
    }

    public function suspendUser(User $user): JsonResponse
    {
        $user->update(['grace_period_ends_at' => now()->addDays(30)]);
        $user->domains()->update(['status' => 'suspended', 'suspended_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function extendGrace(Request $request, User $user): JsonResponse
    {
        $request->validate(['days' => 'required|integer|min:1|max:90']);
        $user->update([
            'grace_period_ends_at' => now()->addDays($request->days),
        ]);

        return response()->json(['success' => true]);
    }

    public function reactivateUser(User $user): JsonResponse
    {
        $user->update(['grace_period_ends_at' => null]);
        $user->domains()->where('status', 'suspended')->update([
            'status'               => 'active',
            'suspended_at'         => null,
            'grace_period_ends_at' => null,
        ]);

        return response()->json(['success' => true]);
    }
}
