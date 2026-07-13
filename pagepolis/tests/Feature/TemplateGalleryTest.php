<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_includes_js_so_3d_hero_and_interactive_templates_preview_correctly(): void
    {
        Template::create([
            'name'       => 'App / SaaS',
            'category'   => 'SaaS',
            'tags'       => ['saas'],
            'html'       => '<div class="hero"><canvas class="hero3d-canvas" data-hero3d></canvas></div>',
            'css'        => 'body{margin:0}',
            'js'         => '(function(){window.__pagepolisHero3dBooted = true;})();',
            'is_premium' => false,
            'is_active'  => true,
        ]);

        $response = $this->get('/plantillas');

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Templates/Index')
            ->has('templates', 1)
            ->where('templates.0.js', '(function(){window.__pagepolisHero3dBooted = true;})();')
        );
    }

    public function test_gallery_excludes_inactive_templates(): void
    {
        Template::create([
            'name'       => 'Plantilla desactivada',
            'category'   => 'Servicios',
            'tags'       => [],
            'html'       => '<h1>Hola</h1>',
            'css'        => '',
            'js'         => '',
            'is_premium' => false,
            'is_active'  => false,
        ]);

        $response = $this->get('/plantillas');

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Templates/Index')
            ->has('templates', 0)
        );
    }
}
