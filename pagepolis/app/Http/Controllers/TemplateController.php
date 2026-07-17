<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(): Response
    {
        $templates = Template::where('is_active', true)
            ->select('id', 'name', 'category', 'thumbnail', 'tags', 'is_premium', 'uses_count', 'html', 'css')
            ->orderByDesc('uses_count')
            ->get()
            ->map(function (Template $template) {
                $template->is_3d = str_contains($template->html, 'data-hero3d');

                return $template;
            });

        $categories = $templates->pluck('category')->unique()->values();

        return Inertia::render('Templates/Index', [
            'templates'  => $templates,
            'categories' => $categories,
            // Motor del hero 3D, compartido por todas las plantillas que lo usan: se
            // inyecta en la vista previa para que el efecto se vea animado antes de
            // elegir la plantilla (no hace nada si la plantilla no tiene el canvas).
            'hero3dJs' => @file_get_contents(database_path('templates/hero3d.js')) ?: '',
        ]);
    }
}
