<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_includes_template_js_so_3d_heroes_render_in_the_preview(): void
    {
        $template = Template::create([
            'name' => 'Plantilla 3D de prueba',
            'category' => 'SaaS',
            'html' => '<canvas class="hero3d-canvas" data-hero3d></canvas>',
            'css' => '.hero{position:relative}',
            'js' => '/* PAGEPOLIS-HERO3D-ENGINE */ console.log("hero3d");',
            'tags' => ['saas'],
            'is_active' => true,
        ]);

        $response = $this->get('/plantillas');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Templates/Index')
            ->has('templates', 1)
            ->where('templates.0.id', $template->id)
            ->where('templates.0.js', $template->js)
        );
    }

    public function test_gallery_only_lists_active_templates(): void
    {
        Template::create([
            'name' => 'Inactiva',
            'category' => 'SaaS',
            'html' => '<div></div>',
            'css' => '',
            'js' => '',
            'is_active' => false,
        ]);

        $response = $this->get('/plantillas');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Templates/Index')
            ->has('templates', 0)
        );
    }
}
