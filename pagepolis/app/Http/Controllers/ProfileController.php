<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail,
            'status'          => session('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|email|max:255|unique:users,email,' . $request->user()->id,
            'whatsapp_phone'  => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9\s\-]{7,20}$/', 'unique:users,whatsapp_phone,' . $request->user()->id],
        ]);

        $request->user()->fill($request->only(['name', 'email', 'whatsapp_phone']));

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Exportación de datos personales (RGPD, derecho de portabilidad).
     * Devuelve un JSON descargable con todos los datos del usuario.
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['projects', 'domains']);

        $data = [
            'exportado_el' => now()->toIso8601String(),
            'usuario' => [
                'id'                => $user->id,
                'nombre'            => $user->name,
                'email'             => $user->email,
                'email_verificado'  => optional($user->email_verified_at)->toIso8601String(),
                'whatsapp'          => $user->whatsapp_phone,
                'rol'               => $user->role,
                'creado_el'         => optional($user->created_at)->toIso8601String(),
                'suscrito'          => $user->isSubscribed(),
            ],
            'proyectos' => $user->projects->map(fn ($p) => [
                'id'           => $p->id,
                'nombre'       => $p->name,
                'slug'         => $p->slug,
                'estado'       => $p->status,
                'publicado_el' => optional($p->published_at)->toIso8601String(),
                'creado_el'    => optional($p->created_at)->toIso8601String(),
                'seo'          => $p->seo_meta,
                'html'         => $p->html,
                'css'          => $p->css,
                'js'           => $p->js,
            ])->all(),
            'dominios' => $user->domains->map(fn ($d) => [
                'dominio'    => $d->domain,
                'tipo'       => $d->type,
                'estado'     => $d->status,
                'expira_el'  => optional($d->expires_at)->toIso8601String(),
            ])->all(),
        ];

        $filename = 'pagepolis-datos-' . $user->id . '-' . now()->format('Y-m-d') . '.json';

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
