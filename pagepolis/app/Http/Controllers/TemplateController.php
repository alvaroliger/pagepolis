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
            ->each(function (Template $template) {
                // Plantillas con hero 3D interactivo (canvas WebGL, ver database/templates/hero3d.js)
                // frente a plantillas clásicas, para que el cliente elija el tipo de página en la galería.
                $template->has_3d = str_contains($template->html, 'data-hero3d');
            });

        $categories = $templates->pluck('category')->unique()->values();

        return Inertia::render('Templates/Index', [
            'templates'  => $templates,
            'categories' => $categories,
        ]);
    }
}
