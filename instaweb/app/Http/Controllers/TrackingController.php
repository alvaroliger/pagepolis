<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Models\Project;
use Illuminate\Http\Response;

class TrackingController extends Controller
{
    /**
     * Endpoint de tracking de visitas para sitios con dominio propio.
     * El script inyectado en el HTML desplegado llama a este endpoint.
     */
    public function track(int $projectId): Response
    {
        // Verificar que el proyecto existe y está publicado
        $exists = Project::where('id', $projectId)
            ->where('status', 'published')
            ->exists();

        if ($exists) {
            PageView::record($projectId);
        }

        // Devuelve un GIF 1x1 transparente
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }
}
