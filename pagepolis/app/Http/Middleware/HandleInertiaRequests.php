<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => $request->user()->name,
                    'email' => $request->user()->email,
                    'role'  => $request->user()->role,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error'   => fn () => $request->session()->get('error'),
                // 'status' es la convención de Breeze (perfil, contraseña, verificación)
                // y la usan también los controladores propios: sin compartirla, esos
                // avisos nunca llegaban al frontend.
                'status'  => fn () => $request->session()->get('status'),
            ],
            // Correo de soporte (configurable), disponible en todas las páginas.
            'support' => [
                'email' => config('pagepolis.support_email'),
            ],
            // Mensajes (leads) sin leer, para el badge de la navegación.
            'leadsUnread' => fn () => $request->user()
                ? \App\Models\Lead::whereIn('project_id', $request->user()->projects()->select('id'))
                    ->whereNull('read_at')->count()
                : 0,
        ]);
    }
}
