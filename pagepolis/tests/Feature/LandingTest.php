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

    /**
     * El JS de la plantilla ya no viaja en las props de la galería (218
     * plantillas × su código eran 2,95 MB por visita, más un iframe por
     * tarjeta). Llega por /plantillas/{id}/render, que es lo que carga la
     * vista previa: el hero 3D tiene que seguir animándose ahí.
     */
    public function test_template_render_endpoint_includes_js_so_3d_heroes_animate_in_preview(): void
    {
        $template = Template::create([
            'name' => 'App / SaaS',
            'category' => 'SaaS',
            'html' => '<canvas class="hero3d-canvas" data-hero3d></canvas>',
            'css' => '',
            'js' => '/* PAGEPOLIS-HERO3D-ENGINE */ console.log("hero3d");',
            'is_active' => true,
        ]);

        $response = $this->get("/plantillas/{$template->id}/render");

        $response->assertStatus(200);
        $response->assertSee('PAGEPOLIS-HERO3D-ENGINE', false);
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
