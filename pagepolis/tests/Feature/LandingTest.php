<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_templates_page_loads(): void
    {
        $response = $this->get('/plantillas');
        $response->assertStatus(200);
    }

    public function test_templates_page_includes_js_for_preview(): void
    {
        // El "js" (motor 3D/tilt) tiene que llegar al front para que la vista
        // previa de plantillas muestre el efecto real, no solo html/css estáticos.
        Template::create([
            'name'       => 'Plantilla con 3D',
            'category'   => 'SaaS',
            'html'       => '<div class="hero"><canvas class="hero3d-canvas" data-hero3d></canvas></div>',
            'css'        => '.hero{height:100%}',
            'js'         => 'console.log("hero3d init");',
            'is_premium' => false,
            'is_active'  => true,
        ]);

        $response = $this->get('/plantillas');

        $response->assertInertia(fn ($page) => $page
            ->component('Templates/Index')
            ->where('templates.0.js', 'console.log("hero3d init");')
        );
    }

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_editor_requires_auth(): void
    {
        $response = $this->get('/editor/1');
        $response->assertRedirect('/login');
    }

    public function test_admin_requires_auth(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }
}
