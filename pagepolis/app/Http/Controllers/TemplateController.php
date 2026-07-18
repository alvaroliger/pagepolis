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
            ->select('id', 'name', 'category', 'thumbnail', 'tags', 'is_premium', 'uses_count', 'html', 'css', 'js')
            ->orderByDesc('uses_count')
            ->get()
            ->map(function (Template $template) {
                $template->is_3d = str_contains($template->html, 'data-hero3d')
                    || str_contains($template->html, 'hero3d-canvas');

                return $template;
            });

        $categories = $templates->pluck('category')->unique()->values();

        return Inertia::render('Templates/Index', [
            'templates'  => $templates,
            'categories' => $categories,
        ]);
    }
}
