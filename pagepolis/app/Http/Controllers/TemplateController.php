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
            ->append('has3d');

        $categories = $templates->pluck('category')->unique()->values();

        return Inertia::render('Templates/Index', [
            'templates'  => $templates,
            'categories' => $categories,
        ]);
    }
}
