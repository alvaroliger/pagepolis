<?php

namespace Tests\Feature;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La galería de plantillas (/plantillas) es donde el cliente elige entre una
 * página "clásica" o una con hero 3D interactivo. Esa distinción se calcula
 * en el controlador a partir del HTML de la plantilla (canvas hero3d-canvas),
 * no de una columna en BD — este test protege que el flag llegue correcto a
 * la vista y que la galería solo muestre plantillas activas.
 */
class TemplateGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function template(array $attrs = []): Template
    {
        return Template::create(array_merge([
            'name'        => 'Plantilla de prueba',
            'category'    => 'Servicios',
            'html'        => '<h1>Hola</h1>',
            'css'         => '',
            'js'          => '',
            'tags'        => ['prueba'],
            'is_premium'  => false,
            'is_active'   => true,
            'uses_count'  => 0,
        ], $attrs));
    }

    public function test_template_with_hero3d_canvas_is_flagged_as_3d(): void
    {
        $this->template([
            'name' => 'SaaS',
            'html' => '<section class="hero"><canvas class="hero3d-canvas" data-hero3d aria-hidden="true"></canvas></section>',
        ]);

        $response = $this->get('/plantillas');

        $response->assertOk();

        $templates = $response->original->getData()['page']['props']['templates'];

        $this->assertTrue(collect($templates)->firstWhere('name', 'SaaS')['has_3d_hero']);
    }

    public function test_template_without_hero3d_canvas_is_flagged_as_classic(): void
    {
        $this->template([
            'name' => 'Restaurante',
            'html' => '<section class="hero"><img src="plato.jpg"></section>',
        ]);

        $response = $this->get('/plantillas');

        $templates = $response->original->getData()['page']['props']['templates'];

        $this->assertFalse(collect($templates)->firstWhere('name', 'Restaurante')['has_3d_hero']);
    }

    public function test_inactive_templates_are_not_shown(): void
    {
        $this->template(['name' => 'Oculta', 'is_active' => false]);

        $response = $this->get('/plantillas');

        $templates = $response->original->getData()['page']['props']['templates'];

        $this->assertNull(collect($templates)->firstWhere('name', 'Oculta'));
    }
}
