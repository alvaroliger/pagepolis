<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Galería de plantillas: el cliente debe poder distinguir de un vistazo las
 * plantillas con hero 3D interactivo (data-hero3d) de las clásicas.
 */
class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function props($response): array
    {
        $response->assertViewHas('page');

        return json_decode(json_encode($response->viewData('page')), true)['props'];
    }

    public function test_marks_templates_with_3d_hero_canvas(): void
    {
        Template::create([
            'name' => 'SaaS 3D', 'category' => 'SaaS', 'tags' => ['saas'],
            'html' => '<header></header><section class="hero"><canvas class="hero3d-canvas" data-hero3d></canvas></section>',
            'css' => '', 'js' => '', 'is_premium' => false, 'is_active' => true,
        ]);

        Template::create([
            'name' => 'Tienda clásica', 'category' => 'E-commerce', 'tags' => ['tienda'],
            'html' => '<header></header><section class="hero"><img src="foto.jpg"></section>',
            'css' => '', 'js' => '', 'is_premium' => false, 'is_active' => true,
        ]);

        $response = $this->get('/plantillas');
        $response->assertStatus(200);

        $templates = collect($this->props($response)['templates'])->keyBy('name');

        $this->assertTrue($templates['SaaS 3D']['has_3d']);
        $this->assertFalse($templates['Tienda clásica']['has_3d']);
    }

    public function test_inactive_templates_are_not_listed(): void
    {
        Template::create([
            'name' => 'Oculta', 'category' => 'Servicios', 'tags' => [],
            'html' => '<div></div>', 'css' => '', 'js' => '', 'is_premium' => false, 'is_active' => false,
        ]);

        $response = $this->get('/plantillas');
        $response->assertStatus(200);

        $this->assertSame([], $this->props($response)['templates']);
    }
}
