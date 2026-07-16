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
            ->each(function (Template $template): void {
                // El hero 3D se marca en el HTML con este canvas (ver database/templates/*.html
                // y AnthropicService::withHero3dEngine); no hay columna dedicada en la tabla,
                // así que basta con detectarlo aquí para que el cliente elija "clásica" o "3D".
                $template->has_3d_hero = str_contains($template->html, 'hero3d-canvas');
            });

        $categories = $templates->pluck('category')->unique()->values();

        return Inertia::render('Templates/Index', [
            'templates'  => $templates,
            'categories' => $categories,
        ]);
    }
}
